import { readFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

interface SqlInsert {
  type: string;
  table?: Array<{ table?: string }>;
  columns?: string[];
  values?: { type?: string; values?: Array<{ value: unknown[] }> };
}

interface SqlParser {
  astify(sql: string, options: { database: string }): unknown;
}

const require = createRequire(import.meta.url);
const parserPackage = require('node-sql-parser') as { Parser: new () => SqlParser };

export interface SeedCategory {
  legacyId: number;
  name: string;
  slug: string;
}

export interface SeedProduct {
  legacyId: number;
  categoryLegacyId: number;
  name: string;
  slug: string;
  description: string;
  priceCents: number;
  originalPriceCents: number | null;
  rating: number;
  reviewCount: number;
  images: string[];
  colour: string;
  isNew: boolean;
  isTopSelling: boolean;
  legacyCreatedAt: Date;
}

export interface ParsedCatalogueSeed {
  categories: SeedCategory[];
  products: SeedProduct[];
  uniqueImageCount: number;
  duplicateImageCount: number;
}

function literalValue(expression: unknown): string | number | null {
  if (typeof expression !== 'object' || expression === null || !('type' in expression) || !('value' in expression)) {
    throw new Error('Seed row contains a non-literal SQL expression.');
  }
  const literal = expression as { type: string; value: unknown };
  if (literal.type === 'null' && literal.value === null) return null;
  if (literal.type === 'single_quote_string' && typeof literal.value === 'string') return literal.value;
  if (literal.type === 'number' && (typeof literal.value === 'string' || typeof literal.value === 'number')) return literal.value;
  throw new Error(`Unsupported SQL seed literal (${literal.type}).`);
}

function decimalToCents(value: string | number | null): number | null {
  if (value === null) return null;
  const raw = String(value);
  const match = /^(\d+)(?:\.(\d{1,2}))?$/.exec(raw);
  if (!match) throw new Error(`Invalid DECIMAL(10,2) catalogue value: ${raw}`);
  const cents = Number(match[1]) * 100 + Number((match[2] ?? '').padEnd(2, '0'));
  if (!Number.isSafeInteger(cents)) throw new Error(`Catalogue price is outside the supported range: ${raw}`);
  return cents;
}

function requireString(value: string | number | null, field: string): string {
  if (typeof value !== 'string' || value.length === 0) throw new Error(`Seed field ${field} must be a non-empty string.`);
  return value;
}

function requireInteger(value: string | number | null, field: string, minimum = 0): number {
  const number = typeof value === 'number' ? value : Number(value);
  if (!Number.isSafeInteger(number) || number < minimum) throw new Error(`Seed field ${field} must be an integer >= ${minimum}.`);
  return number;
}

function requireDate(value: string | number | null, field: string): Date {
  const raw = requireString(value, field);
  const date = new Date(`${raw.replace(' ', 'T')}Z`);
  if (Number.isNaN(date.valueOf())) throw new Error(`Seed field ${field} must be a valid SQL datetime.`);
  return date;
}

function insertRows(ast: unknown[], tableName: string, expectedColumns: string[]): Array<Record<string, string | number | null>> {
  const statements = (ast as SqlInsert[]).filter((statement) =>
    statement.type === 'insert' && Array.isArray(statement.table) && statement.table[0]?.table === tableName,
  );
  if (statements.length !== 1) throw new Error(`Expected one INSERT statement for ${tableName}.`);
  const statement = statements[0];
  if (!Array.isArray(statement.columns) || statement.columns.join('|') !== expectedColumns.join('|')) {
    throw new Error(`The ${tableName} seed columns do not match the expected mapping.`);
  }
  if (!statement.values || statement.values.type !== 'values' || !Array.isArray(statement.values.values)) {
    throw new Error(`The ${tableName} seed must contain literal VALUES rows.`);
  }
  return statement.values.values.map((row) => {
    if (row.value.length !== expectedColumns.length) throw new Error(`A ${tableName} row has the wrong number of values.`);
    return Object.fromEntries(expectedColumns.map((column, index) => [column, literalValue(row.value[index])]));
  });
}

export function parseCatalogueSeed(sql: string): ParsedCatalogueSeed {
  const parsed = new parserPackage.Parser().astify(sql, { database: 'MySQL' });
  if (!Array.isArray(parsed)) throw new Error('Catalogue seed parser did not return a statement list.');
  const categories = insertRows(parsed, 'categories', ['id', 'name', 'slug']).map((row) => ({
    legacyId: requireInteger(row.id, 'categories.id', 1),
    name: requireString(row.name, 'categories.name'),
    slug: requireString(row.slug, 'categories.slug'),
  }));

  const productColumns = [
    'id', 'category_id', 'name', 'slug', 'description', 'price', 'original_price', 'rating', 'review_count',
    'image', 'image_2', 'image_3', 'image_4', 'colour', 'is_new', 'is_top_selling', 'created_at',
  ];
  const products = insertRows(parsed, 'products', productColumns).map((row) => {
    const images = ['image', 'image_2', 'image_3', 'image_4']
      .map((field) => row[field])
      .filter((value): value is string => typeof value === 'string' && value.trim() !== '');
    if (images.length === 0 || images.length > 4) throw new Error(`Product ${String(row.id)} must have one to four source images.`);
    const rating = Number(row.rating);
    if (!Number.isFinite(rating) || rating < 0 || rating > 5) throw new Error(`Product ${String(row.id)} has an invalid rating.`);
    return {
      legacyId: requireInteger(row.id, 'products.id', 1),
      categoryLegacyId: requireInteger(row.category_id, 'products.category_id', 1),
      name: requireString(row.name, 'products.name'),
      slug: requireString(row.slug, 'products.slug'),
      description: requireString(row.description, 'products.description'),
      priceCents: decimalToCents(row.price)!,
      originalPriceCents: decimalToCents(row.original_price),
      rating,
      reviewCount: requireInteger(row.review_count, 'products.review_count'),
      images,
      colour: requireString(row.colour, 'products.colour'),
      isNew: requireInteger(row.is_new, 'products.is_new') === 1,
      isTopSelling: requireInteger(row.is_top_selling, 'products.is_top_selling') === 1,
      legacyCreatedAt: requireDate(row.created_at, 'products.created_at'),
    };
  });

  if (categories.length !== 6 || products.length !== 64) {
    throw new Error(`Expected the verified legacy seed to contain 6 categories and 64 products; parsed ${categories.length} and ${products.length}.`);
  }
  const categoryIds = new Set(categories.map((category) => category.legacyId));
  if (new Set(categories.map((category) => category.slug)).size !== categories.length) throw new Error('Category slugs must be unique.');
  if (new Set(products.map((product) => product.legacyId)).size !== products.length) throw new Error('Product legacy IDs must be unique.');
  if (new Set(products.map((product) => product.slug)).size !== products.length) throw new Error('Product slugs must be unique.');
  const orderedIds = products.map((product) => product.legacyId).sort((left, right) => left - right);
  if (orderedIds.some((id, index) => id !== index + 1)) throw new Error('The verified seed must retain its contiguous legacy product IDs 1–64.');
  for (const product of products) if (!categoryIds.has(product.categoryLegacyId)) throw new Error(`Product ${product.legacyId} references a missing category.`);
  const allImages = products.flatMap((product) => product.images);
  const uniqueImages = new Set(allImages);

  return {
    categories,
    products,
    uniqueImageCount: uniqueImages.size,
    duplicateImageCount: allImages.length - uniqueImages.size,
  };
}

export async function readLegacyCatalogueSeed(): Promise<ParsedCatalogueSeed> {
  const seedPath = fileURLToPath(new URL('../../../../database/f2b_product_seed.sql', import.meta.url));
  return parseCatalogueSeed(await readFile(seedPath, 'utf8'));
}
