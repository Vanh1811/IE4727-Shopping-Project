import { Router, type Request } from 'express';
import { z } from 'zod';
import { ApiError } from '../middleware/errors.js';
import { productDto } from '../catalogue/dto.js';
import { buildCatalogueFilter, parsePositiveInteger } from '../catalogue/service.js';
import { findProductByIdentifier, getCatalogueMeta, getHomeCatalogue, listProducts } from '../catalogue/repository.js';

export const catalogueRouter = Router();

const searchSchema = z.string().max(2000, 'Search text is too long.');

function queryValues(request: Request): URLSearchParams {
  return new URL(request.originalUrl, 'http://127.0.0.1').searchParams;
}

function firstQueryValue(params: URLSearchParams, name: string, alias?: string): string | null {
  return params.get(name) ?? (alias ? params.get(alias) : null);
}

catalogueRouter.get('/catalogue/meta', async (_request, response) => {
  response.json({ data: await getCatalogueMeta(), error: null });
});

catalogueRouter.get('/products', async (request, response) => {
  const params = queryValues(request);
  const search = (firstQueryValue(params, 'search', 'q') ?? '').trim();
  const searchResult = searchSchema.safeParse(search);
  if (!searchResult.success) throw new ApiError(422, 'INVALID_SEARCH', searchResult.error.issues[0].message);

  const page = parsePositiveInteger(params.get('page'), 1, 1_000_000);
  const limit = parsePositiveInteger(params.get('limit'), 100, 100);
  if (page === null || limit === null) throw new ApiError(400, 'INVALID_PAGINATION', 'Page and limit must be positive integers within the supported range.');

  const metadata = await getCatalogueMeta();
  const knownCategorySlugs = new Set(metadata.categories.map((category) => category.slug));
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
  }, metadata.colours);
  const { products, total } = await listProducts(result);
  response.json({
    data: {
      items: products.map(productDto),
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
  const product = await findProductByIdentifier(numericId === null ? identifier : numericId);
  if (!product) throw new ApiError(404, 'PRODUCT_NOT_FOUND', 'The requested product could not be found.');
  if (!product.images.length) throw new ApiError(404, 'PRODUCT_NOT_FOUND', 'The requested product could not be found.');
  response.json({ data: productDto(product), error: null });
});

catalogueRouter.get('/home', async (_request, response) => {
  const home = await getHomeCatalogue();
  response.json({ data: { ...home, newArrivals: home.newArrivals.map(productDto), topSelling: home.topSelling.map(productDto) }, error: null });
});
