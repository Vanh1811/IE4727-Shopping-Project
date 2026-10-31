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
export type Cart = { items: { key: string; productId: number; slug: string; name: string; image: string; categorySlug: string; size: string; colour: string; quantity: number; priceCents: number; lineTotalCents: number }[]; subtotalCents: number; discountCents: number; deliveryCents: number; totalCents: number; itemCount: number };
export type CheckoutValues = { full_name: string; email: string; phone: string; postal_code: string; delivery_address: string; special_instructions: string };
export type SavedOrder = { orderNumber: number; status: string; createdAt: string; fullName: string; items: { id: string; productId: number; productName: string; image: string; size: string; colour: string; unitPriceCents: number; quantity: number; lineTotalCents: number }[]; subtotalCents: number; discountCents: number; deliveryCents: number; totalCents: number; emailStatus: string };
export class ApiRequestError extends Error { constructor(message: string, public readonly data: unknown) { super(message); this.name = 'ApiRequestError'; } }

async function mutate<T>(path: string, method: 'POST'|'PATCH'|'DELETE', body?: unknown, headers: Record<string,string> = {}): Promise<T> {
  const csrfResponse = await fetch('/api/v1/session/csrf', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
  const csrfPayload = await csrfResponse.json().catch(() => null) as { data?: { csrfToken?: string } } | null;
  const csrfToken = csrfPayload?.data?.csrfToken;
  if (!csrfResponse.ok || !csrfToken) throw new Error('Your session has expired. Refresh the page and try again.');
  const response = await fetch(`/api/v1${path}`, { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken, ...headers }, ...(body === undefined ? {} : { body: JSON.stringify(body) }) });
  const payload = await response.json().catch(() => null) as { data?: T; error?: { message?: string } } | null;
  if (!response.ok || !payload?.data) throw new ApiRequestError(payload?.error?.message ?? 'The request could not be completed.', payload?.data);
  return payload.data;
}

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
  cart: () => get<Cart>('/cart'),
  addToCart: (item: { productId: number; size: string; colour: string; quantity: number }) => mutate<Cart>('/cart/items', 'POST', item),
  updateCartItem: (key: string, quantity: number) => mutate<Cart>(`/cart/items/${encodeURIComponent(key)}`, 'PATCH', { quantity }),
  removeCartItem: (key: string) => mutate<Cart>(`/cart/items/${encodeURIComponent(key)}`, 'DELETE'),
  checkout: () => get<Cart>('/checkout'),
  submitCheckout: (values: CheckoutValues, key: string) => mutate<{ orderNumber: number; status: string; confirmationUrl: string }>('/checkout', 'POST', values, { 'Idempotency-Key': key }),
  confirmation: (number: string) => get<{ orderNumber: number; status: string; items: Cart['items']; subtotalCents: number; discountCents: number; deliveryCents: number; totalCents: number; accessCode: string | null; emailStatus: string }>(`/orders/${encodeURIComponent(number)}/confirmation`),
  orders: () => get<{ orders: SavedOrder[] }>('/orders'),
  order: (number: string) => get<{ order: SavedOrder }>(`/orders/${encodeURIComponent(number)}`),
  lookupOrder: (orderNumber: string, accessCode: string) => mutate<{ order: SavedOrder }>('/orders/access', 'POST', { orderNumber, accessCode }),
};

export const money = (cents: number) => new Intl.NumberFormat('en-SG', { style: 'currency', currency: 'SGD' }).format(cents / 100);
