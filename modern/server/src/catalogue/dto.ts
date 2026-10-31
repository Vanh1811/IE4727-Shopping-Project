import type { CatalogueProduct } from './repository.js';
import { colourHex, roundLegacy } from './service.js';

export function productDto(product: CatalogueProduct) {
  const originalPriceCents = product.originalPriceCents ?? null;
  const discountPercent = originalPriceCents !== null && originalPriceCents > product.priceCents
    ? roundLegacy((1 - product.priceCents / originalPriceCents) * 100)
    : 0;
  return {
    id: product.legacyId,
    slug: product.slug,
    name: product.name,
    description: product.description,
    category: product.categoryRef.name,
    categorySlug: product.categorySlug,
    priceCents: product.priceCents,
    originalPriceCents,
    discountPercent,
    rating: product.rating,
    starCount: Math.max(0, Math.min(5, roundLegacy(product.rating))),
    reviewCount: product.reviewCount,
    images: product.images,
    image: product.images[0],
    colour: product.colour,
    colourHex: colourHex(product.colour),
    isNew: product.isNew,
    isTopSelling: product.isTopSelling,
    createdAt: product.legacyCreatedAt,
  };
}
