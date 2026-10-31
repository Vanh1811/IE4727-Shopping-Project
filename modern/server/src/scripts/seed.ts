import 'dotenv/config';
import type { RowDataPacket } from 'mysql2';
import { resolve } from 'node:path';
import { env } from '../config/env.js';
import { assertSafeDatabaseTarget } from '../db/guard.js';
import { centsToDecimal } from '../db/money.js';
import { pool } from '../db/pool.js';
import { readLegacyCatalogueSeed } from '../catalogue/seedParser.js';

async function main(): Promise<void> {
  assertSafeDatabaseTarget();
  const parsed = await readLegacyCatalogueSeed();
  for (const category of parsed.categories) {
    await pool.execute(
      `INSERT INTO categories (id, name, slug) VALUES (?, ?, ?)
       ON DUPLICATE KEY UPDATE name = VALUES(name), slug = VALUES(slug)`,
      [category.legacyId, category.name, category.slug],
    );
  }

  for (const product of parsed.products) {
    const images = [...product.images, null, null, null, null].slice(0, 4);
    await pool.execute(
      `INSERT INTO products (
        id, category_id, name, slug, description, price, original_price, rating, review_count,
        image, image_2, image_3, image_4, colour, is_new, is_top_selling, created_at
       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE category_id = VALUES(category_id), name = VALUES(name),
        slug = VALUES(slug), description = VALUES(description), price = VALUES(price),
        original_price = VALUES(original_price), rating = VALUES(rating), review_count = VALUES(review_count),
        image = VALUES(image), image_2 = VALUES(image_2), image_3 = VALUES(image_3), image_4 = VALUES(image_4),
        colour = VALUES(colour), is_new = VALUES(is_new), is_top_selling = VALUES(is_top_selling),
        created_at = VALUES(created_at)`,
      [
        product.legacyId, product.categoryLegacyId, product.name, product.slug, product.description,
        centsToDecimal(product.priceCents), product.originalPriceCents === null ? null : centsToDecimal(product.originalPriceCents),
        product.rating.toFixed(1), product.reviewCount,
        images[0], images[1], images[2], images[3], product.colour,
        Number(product.isNew), Number(product.isTopSelling), product.legacyCreatedAt.toISOString().slice(0, 19).replace('T', ' '),
      ],
    );
  }

  const [countRows] = await pool.execute<(RowDataPacket & { categories: number | string; products: number | string; images: number | string })[]>(
    `SELECT
        (SELECT COUNT(*) FROM categories WHERE id IN (${parsed.categories.map(() => '?').join(',')})) AS categories,
        (SELECT COUNT(*) FROM products WHERE id IN (${parsed.products.map(() => '?').join(',')})) AS products,
        (SELECT SUM((image IS NOT NULL AND image <> '') + (image_2 IS NOT NULL AND image_2 <> '') +
          (image_3 IS NOT NULL AND image_3 <> '') + (image_4 IS NOT NULL AND image_4 <> '')) FROM products
          WHERE id IN (${parsed.products.map(() => '?').join(',')})) AS images`,
      [
        ...parsed.categories.map((category) => category.legacyId),
        ...parsed.products.map((product) => product.legacyId),
        ...parsed.products.map((product) => product.legacyId),
      ],
  );
  const counts = countRows[0];
  if (Number(counts?.categories) !== 6 || Number(counts?.products) !== 64 || Number(counts?.images) !== parsed.products.reduce((sum, product) => sum + product.images.length, 0)) {
    throw new Error('Modern catalogue seed verification failed; expected all six categories, 64 products, and the source image count.');
  }
  process.stdout.write([
    `Seed target: ${env.DB_NAME}`,
    `Seed source: ${resolve(process.cwd(), '../../database/f2b_product_seed.sql')}`,
    `Categories: ${counts?.categories}`,
    `Products: ${counts?.products}`,
    `Image URLs retained: ${counts?.images}`,
    `Unique image URLs: ${parsed.uniqueImageCount}`,
    `Duplicate image URLs: ${parsed.duplicateImageCount}`,
  ].join('\n') + '\n');
}

main().catch((error: unknown) => {
  const message = error instanceof Error ? error.message : 'Unknown catalogue seed error.';
  process.stderr.write(`${message}\n`);
  process.exitCode = 1;
}).finally(async () => { await pool.end().catch(() => undefined); });
