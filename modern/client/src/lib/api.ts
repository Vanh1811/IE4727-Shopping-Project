export type Product = {
  id: number;
  slug: string;
  name: string;
  description: string;
  category: string;
  categorySlug: string;
  priceCents: number;
  originalPriceCents: number | null;
  discountPercent: number;
  rating: number;
  starCount: number;
  reviewCount: number;
  images: string[];
  image: string;
  colour: string;
  colourHex: string;
  isNew: boolean;
  isTopSelling: boolean;
  createdAt: string;
};

export type Category = { id: number; name: string; slug: string };
export type CatalogueMeta = { categories: Category[]; colours: string[]; priceFloor: number; priceCeiling: number };
export type ProductList = {
  items: Product[];
  filters: { search: string; categories: string[]; colours: string[]; minPrice: string | null; maxPrice: string | null; sort: string; notice: string | null };
  pagination: { page: number; limit: number; total: number; totalPages: number };
};
export type HomeData = { stats: { products: number; categories: number; averageRating: number | null }; newArrivals: Product[]; topSelling: Product[] };

async function get<T>(path: string): Promise<T> {
  let response: Response;
  try {
    response = await fetch(`/api/v1${path}`, { headers: { Accept: 'application/json' } });
  } catch {
    throw new Error('The shop is temporarily unavailable. Please try again shortly.');
  }
  const payload = await response.json().catch(() => null) as { data?: T; error?: { message?: string } } | null;
  if (!response.ok || !payload?.data) throw new Error(payload?.error?.message ?? 'The shop is temporarily unavailable. Please try again shortly.');
  return payload.data;
}

export const api = {
  home: () => get<HomeData>('/home'),
  meta: () => get<CatalogueMeta>('/catalogue/meta'),
  products: (query: URLSearchParams) => get<ProductList>(`/products${query.size ? `?${query.toString()}` : ''}`),
  product: (id: string) => get<Product>(`/products/${encodeURIComponent(id)}`),
};

export const money = (cents: number) => new Intl.NumberFormat('en-SG', { style: 'currency', currency: 'SGD' }).format(cents / 100);
