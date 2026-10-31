import 'dotenv/config';
import mongoose from 'mongoose';
import { resolve } from 'node:path';
import { env } from '../config/env.js';
import { Category } from '../models/Category.js';
import { Product } from '../models/Product.js';
import { readLegacyCatalogueSeed } from '../catalogue/seedParser.js';

function assertSeedTarget(uri: string): void {
  const parsed = new URL(uri);
  const databaseName = parsed.pathname.replace(/^\//, '');
  if (!['127.0.0.1', 'localhost', '::1'].includes(parsed.hostname)) {
    throw new Error('Catalogue seeding is restricted to a loopback MongoDB host.');
  }
  if (databaseName !== 'f2b_shop_modern') {
    throw new Error('Catalogue seeding requires the explicitly named f2b_shop_modern database.');
  }
}

async function main(): Promise<void> {
  if (!env.MONGODB_URI) throw new Error('MONGODB_URI is required for catalogue seeding.');
  assertSeedTarget(env.MONGODB_URI);
  const parsed = await readLegacyCatalogueSeed();
  await mongoose.connect(env.MONGODB_URI, { serverSelectionTimeoutMS: 5000 });

  await Category.bulkWrite(parsed.categories.map((category) => ({
    updateOne: {
      filter: { legacyId: category.legacyId },
      update: { $set: { name: category.name, slug: category.slug }, $setOnInsert: { legacyId: category.legacyId } },
      upsert: true,
    },
  })), { ordered: true });

  const storedCategories = await Category.find({}, { legacyId: 1, name: 1, slug: 1 }).lean();
  const categoryByLegacyId = new Map(storedCategories.map((category) => [category.legacyId, category]));
  const productWrites = parsed.products.map((product) => {
    const category = categoryByLegacyId.get(product.categoryLegacyId);
    if (!category) throw new Error(`Category ${product.categoryLegacyId} was not saved.`);
    return {
      updateOne: {
        filter: { legacyId: product.legacyId },
        update: {
          $set: {
            categoryRef: category._id,
            categorySlug: category.slug,
            name: product.name,
            slug: product.slug,
            description: product.description,
            priceCents: product.priceCents,
            originalPriceCents: product.originalPriceCents,
            rating: product.rating,
            reviewCount: product.reviewCount,
            images: product.images,
            colour: product.colour,
            isNew: product.isNew,
            isTopSelling: product.isTopSelling,
            legacyCreatedAt: product.legacyCreatedAt,
          },
          $setOnInsert: { legacyId: product.legacyId },
        },
        upsert: true,
      },
    };
  });
  await Product.bulkWrite(productWrites, { ordered: true });

  const [categoryCount, productCount] = await Promise.all([
    Category.countDocuments({ legacyId: { $in: parsed.categories.map((category) => category.legacyId) } }),
    Product.countDocuments({ legacyId: { $in: parsed.products.map((product) => product.legacyId) } }),
  ]);
  if (categoryCount !== parsed.categories.length || productCount !== parsed.products.length) {
    throw new Error(`Seed verification failed: saved ${categoryCount} categories and ${productCount} products.`);
  }
  process.stdout.write([
    `Seed source: ${resolve(process.cwd(), '../../database/f2b_product_seed.sql')}`,
    `Categories: ${categoryCount}`,
    `Products: ${productCount}`,
    `Image URLs retained: ${parsed.products.reduce((count, product) => count + product.images.length, 0)}`,
    `Unique image URLs: ${parsed.uniqueImageCount}`,
    `Duplicate image URLs: ${parsed.duplicateImageCount}`,
  ].join('\n') + '\n');
}

main().catch((error: unknown) => {
  const message = error instanceof Error ? error.message : 'Unknown catalogue seed error';
  process.stderr.write(`${message}\n`);
  process.exitCode = 1;
}).finally(async () => {
  await mongoose.disconnect().catch(() => undefined);
});
