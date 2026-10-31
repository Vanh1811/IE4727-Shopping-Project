import { Router, type Request } from 'express';
import { z } from 'zod';
import { ApiError } from '../middleware/errors.js';
import { Category } from '../models/Category.js';
import { Product, type PopulatedProduct } from '../models/Product.js';
import { productDto } from '../catalogue/dto.js';
import { buildCatalogueFilter, parsePositiveInteger } from '../catalogue/service.js';

export const catalogueRouter = Router();

const searchSchema = z.string().max(2000, 'Search text is too long.');

function queryValues(request: Request): URLSearchParams {
  return new URL(request.originalUrl, 'http://127.0.0.1').searchParams;
}

function firstQueryValue(params: URLSearchParams, name: string, alias?: string): string | null {
  return params.get(name) ?? (alias ? params.get(alias) : null);
}

catalogueRouter.get('/catalogue/meta', async (_request, response) => {
  const [categories, colours, lowest, highest] = await Promise.all([
    Category.find({}, { legacyId: 1, name: 1, slug: 1 }).sort({ legacyId: 1 }).lean(),
    Product.distinct('colour'),
    Product.findOne({}, { priceCents: 1 }).sort({ priceCents: 1 }).lean(),
    Product.findOne({}, { priceCents: 1 }).sort({ priceCents: -1 }).lean(),
  ]);
  response.json({
    data: {
      categories: categories.map(({ legacyId, name, slug }) => ({ id: legacyId, name, slug })),
      colours: colours.filter((colour): colour is string => typeof colour === 'string' && colour.length > 0).sort((a, b) => a.localeCompare(b)),
      priceFloor: lowest ? Math.floor(lowest.priceCents / 100) : 0,
      priceCeiling: highest ? Math.ceil(highest.priceCents / 100) : 200,
    },
    error: null,
  });
});

catalogueRouter.get('/products', async (request, response) => {
  const params = queryValues(request);
  const search = (firstQueryValue(params, 'search', 'q') ?? '').trim();
  const searchResult = searchSchema.safeParse(search);
  if (!searchResult.success) throw new ApiError(422, 'INVALID_SEARCH', searchResult.error.issues[0].message);

  const page = parsePositiveInteger(params.get('page'), 1, 1_000_000);
  const limit = parsePositiveInteger(params.get('limit'), 100, 100);
  if (page === null || limit === null) throw new ApiError(400, 'INVALID_PAGINATION', 'Page and limit must be positive integers within the supported range.');

  const [availableCategories, availableColours] = await Promise.all([
    Category.distinct('slug'),
    Product.distinct('colour'),
  ]);
  const knownCategorySlugs = new Set(availableCategories);
  const requestedCategories = params.getAll('category').filter((slug) => knownCategorySlugs.has(slug));
  const result = buildCatalogueFilter({
    search,
    categories: requestedCategories,
    colours: params.getAll('colour'),
    minPrice: params.has('min_price') ? params.get('min_price') : null,
    maxPrice: params.has('max_price') ? params.get('max_price') : null,
    sort: params.get('sort') ?? 'featured',
    page,
    limit,
  }, availableColours);

  const skip = (result.page - 1) * result.limit;
  const total = result.validPriceRange ? await Product.countDocuments(result.filter) : 0;
  const products = result.validPriceRange
    ? await Product.find(result.filter)
      .sort(result.sort)
      .skip(skip)
      .limit(result.limit)
      .populate('categoryRef', 'name slug')
      .lean()
    : [];
  response.json({
    data: {
      items: products.map((product) => productDto(product as unknown as PopulatedProduct)),
      filters: {
        search,
        categories: result.appliedCategories,
        colours: result.appliedColours,
        minPrice: params.get('min_price'),
        maxPrice: params.get('max_price'),
        sort: result.sortKey,
        notice: result.notice,
      },
      pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
    },
    error: null,
  });
});

catalogueRouter.get('/products/:id', async (request, response) => {
  const identifier = request.params.id;
  const numericId = /^\d+$/.test(identifier) ? Number(identifier) : null;
  if (numericId !== null && (!Number.isSafeInteger(numericId) || numericId < 1)) {
    throw new ApiError(400, 'INVALID_PRODUCT_ID', 'Product ID must be a positive integer or slug.');
  }
  const product = await Product.findOne(numericId === null ? { slug: identifier } : { legacyId: numericId })
    .populate('categoryRef', 'name slug')
    .lean();
  if (!product) throw new ApiError(404, 'PRODUCT_NOT_FOUND', 'The requested product could not be found.');
  if (!product.images.length) throw new ApiError(404, 'PRODUCT_NOT_FOUND', 'The requested product could not be found.');
  response.json({ data: productDto(product as unknown as PopulatedProduct), error: null });
});

catalogueRouter.get('/home', async (_request, response) => {
  const [newArrivals, topSelling, productCount, categoryCount, ratingResult] = await Promise.all([
    Product.find({ isNew: true }).sort({ legacyCreatedAt: -1, legacyId: -1 }).limit(4).populate('categoryRef', 'name slug').lean(),
    Product.find({ isTopSelling: true }).sort({ legacyCreatedAt: -1, legacyId: -1 }).limit(4).populate('categoryRef', 'name slug').lean(),
    Product.countDocuments(),
    Category.countDocuments(),
    Product.aggregate<{ averageRating: number }>([{ $group: { _id: null, averageRating: { $avg: '$rating' } } }]),
  ]);
  response.json({
    data: {
      stats: {
        products: productCount,
        categories: categoryCount,
        averageRating: ratingResult[0]?.averageRating ?? null,
      },
      newArrivals: newArrivals.map((product) => productDto(product as unknown as PopulatedProduct)),
      topSelling: topSelling.map((product) => productDto(product as unknown as PopulatedProduct)),
    },
    error: null,
  });
});
