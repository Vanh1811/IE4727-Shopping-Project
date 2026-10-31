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
export type StaffOrder = Omit<SavedOrder, 'items'> & { email: string; phone: string; postalCode: string; deliveryAddress: string; specialInstructions: string; items: (SavedOrder['items'][number] & { mockup: { _id: string; previewImage: string; printPosition: string; printColour: string; customText: string; status: string; revision: number } | null })[] };
export type MockupView = { mockupId: string; orderNumber: number; productName: string; size: string; colour: string; previewImage: string; printPosition: string; printColour: string; customText: string; status: string; revision: number; reviews: { decision: string; changeRequest: string; revision: number; createdAt: string }[] };
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
  staffSession: () => get<{ configured: boolean; authenticated: boolean }>('/staff/session'),
  staffLogin: (password: string) => mutate<{ authenticated: boolean }>('/staff/login', 'POST', { password }),
  staffLogout: () => mutate<{ authenticated: boolean }>('/staff/logout', 'POST', {}),
  staffOrders: (status = '', page = 1) => { const query = new URLSearchParams({ page: String(page) }); if (status) query.set('status', status); return get<{ orders: StaffOrder[]; pagination: { page: number; limit: number; total: number; totalPages: number } }>(`/staff/orders?${query.toString()}`); },
  staffAssets: () => get<{ assets: string[] }>('/staff/mockup-assets'),
  publishMockup: (orderNumber: number, itemId: string, details: { previewImage: string; printPosition: string; printColour: string; customText: string }) => mutate<{ mockupId: string; revision: number }>(`/staff/orders/${orderNumber}/items/${itemId}/mockup`, 'POST', details),
  setOrderStatus: (orderNumber: number, status: 'PROCESSING'|'COMPLETED') => mutate<{ orderNumber: number; status: string }>(`/staff/orders/${orderNumber}/status`, 'POST', { status }),
  mockup: (id: string) => get<MockupView>(`/mockups/${encodeURIComponent(id)}`),
  reviewMockup: (id: string, expectedRevision: number, decision: 'approve'|'request_changes', changeRequest = '') => mutate<{ status: string; orderStatus: string }>(`/mockups/${encodeURIComponent(id)}/review`, 'POST', { expectedRevision, decision, changeRequest }),
};

export const money = (cents: number) => new Intl.NumberFormat('en-SG', { style: 'currency', currency: 'SGD' }).format(cents / 100);
