import type { RowDataPacket } from 'mysql2';
import { decimalToCents } from '../db/money.js';
import { pool } from '../db/pool.js';
import type { FilterResult } from './service.js';

export interface CatalogueProduct {
  legacyId: number;
  slug: string;
  name: string;
  description: string;
  categoryRef: { name: string; slug: string };
  categorySlug: string;
  priceCents: number;
  originalPriceCents: number | null;
  rating: number;
  reviewCount: number;
  images: string[];
  colour: string;
  isNew: boolean;
  isTopSelling: boolean;
  legacyCreatedAt: string;
}

interface ProductRow extends RowDataPacket {
  id: number | string;
  slug: string;
  name: string;
  description: string;
  category_name: string;
  category_slug: string;
  price: string | number;
  original_price: string | number | null;
  rating: string | number;
  review_count: number | string;
  image: string;
  image_2: string | null;
  image_3: string | null;
  image_4: string | null;
  colour: string;
  is_new: number | boolean;
  is_top_selling: number | boolean;
  created_at: string | Date;
}

const productSelect = `SELECT p.id, p.slug, p.name, p.description,
  c.name AS category_name, c.slug AS category_slug,
  p.price, p.original_price, p.rating, p.review_count,
  p.image, p.image_2, p.image_3, p.image_4, p.colour,
  p.is_new, p.is_top_selling, p.created_at`;
const productFrom = ' FROM products p INNER JOIN categories c ON c.id = p.category_id';

function toIso(value: string | Date): string {
  if (value instanceof Date) return value.toISOString();
  const normalized = value.includes('T') ? value : `${value.replace(' ', 'T')}Z`;
  return new Date(normalized).toISOString();
}

function mapProduct(row: ProductRow): CatalogueProduct {
  return {
    legacyId: Number(row.id),
    slug: row.slug,
    name: row.name,
    description: row.description,
    categoryRef: { name: row.category_name, slug: row.category_slug },
    categorySlug: row.category_slug,
    priceCents: decimalToCents(row.price)!,
    originalPriceCents: decimalToCents(row.original_price),
    rating: Number(row.rating),
    reviewCount: Number(row.review_count),
    images: [row.image, row.image_2, row.image_3, row.image_4].filter((image): image is string => typeof image === 'string' && image.length > 0),
    colour: row.colour,
    isNew: Boolean(row.is_new),
    isTopSelling: Boolean(row.is_top_selling),
    legacyCreatedAt: toIso(row.created_at),
  };
}

export async function getCatalogueMeta() {
  const [[categories], [colours], [bounds]] = await Promise.all([
    pool.query<(RowDataPacket & { id: number; name: string; slug: string })[]>('SELECT id, name, slug FROM categories ORDER BY id'),
    pool.query<(RowDataPacket & { colour: string })[]>("SELECT DISTINCT colour FROM products WHERE colour <> '' ORDER BY colour"),
    pool.query<(RowDataPacket & { lowest: string | null; highest: string | null })[]>('SELECT MIN(price) AS lowest, MAX(price) AS highest FROM products'),
  ]);
  const lowest = decimalToCents(bounds[0]?.lowest ?? null);
  const highest = decimalToCents(bounds[0]?.highest ?? null);
  return {
    categories: categories.map((category) => ({ id: Number(category.id), name: category.name, slug: category.slug })),
    colours: colours.map((row) => row.colour).filter(Boolean),
    priceFloor: lowest === null ? 0 : Math.floor(lowest / 100),
    priceCeiling: highest === null ? 200 : Math.ceil(highest / 100),
  };
}

export async function listProducts(result: FilterResult) {
  if (!result.validPriceRange) return { products: [] as CatalogueProduct[], total: 0 };
  const [counts] = await pool.execute<(RowDataPacket & { total: number | string })[]>(
    `SELECT COUNT(*) AS total${productFrom}${result.whereSql}`,
    result.parameters,
  );
  const offset = (result.page - 1) * result.limit;
  const [rows] = await pool.execute<ProductRow[]>(
    `${productSelect}${productFrom}${result.whereSql} ORDER BY ${result.orderBy} LIMIT ? OFFSET ?`,
    [...result.parameters, result.limit, offset],
  );
  return { products: rows.map(mapProduct), total: Number(counts[0]?.total ?? 0) };
}

export async function findProductByIdentifier(identifier: number | string): Promise<CatalogueProduct | null> {
  const byId = typeof identifier === 'number';
  const [rows] = await pool.execute<ProductRow[]>(
    `${productSelect}${productFrom} WHERE p.${byId ? 'id' : 'slug'} = ? LIMIT 1`, [identifier],
  );
  return rows[0] ? mapProduct(rows[0]) : null;
}

export async function findProductsByIds(ids: number[]): Promise<CatalogueProduct[]> {
  const uniqueIds = [...new Set(ids)];
  if (!uniqueIds.length) return [];
  const placeholders = uniqueIds.map(() => '?').join(',');
  const [rows] = await pool.execute<ProductRow[]>(
    `${productSelect}${productFrom} WHERE p.id IN (${placeholders})`, uniqueIds,
  );
  return rows.map(mapProduct);
}

export async function getHomeCatalogue() {
  const [[newRows], [topRows], [stats]] = await Promise.all([
    pool.execute<ProductRow[]>(`${productSelect}${productFrom} WHERE p.is_new = 1 ORDER BY p.created_at DESC, p.id DESC LIMIT 4`),
    pool.execute<ProductRow[]>(`${productSelect}${productFrom} WHERE p.is_top_selling = 1 ORDER BY p.created_at DESC, p.id DESC LIMIT 4`),
    pool.query<(RowDataPacket & { products: number; categories: number; average_rating: string | number | null })[]>(
      'SELECT (SELECT COUNT(*) FROM products) AS products, (SELECT COUNT(*) FROM categories) AS categories, (SELECT AVG(rating) FROM products) AS average_rating',
    ),
  ]);
  return {
    stats: { products: Number(stats[0]?.products ?? 0), categories: Number(stats[0]?.categories ?? 0), averageRating: stats[0]?.average_rating === null ? null : Number(stats[0]?.average_rating) },
    newArrivals: newRows.map(mapProduct),
    topSelling: topRows.map(mapProduct),
  };
}
