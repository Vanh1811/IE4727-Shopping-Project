import { useState } from 'react';
import { Link } from 'react-router-dom';
import { money, type Product } from '../lib/api';

export function ProductCard({ product }: { product: Product }) {
  const [failed, setFailed] = useState(false);
  return <article className="group min-w-0">
    <Link to={`/product/${product.id}`} className="block overflow-hidden rounded-sm bg-surface" aria-label={`View ${product.name}`}>
      {failed ? <div className="grid aspect-[4/5] place-items-center px-5 text-center text-sm text-muted">Image unavailable</div> : <img className="aspect-[4/5] w-full object-cover transition duration-500 group-hover:scale-[1.03]" src={product.image} alt={product.name} loading="lazy" onError={() => setFailed(true)} />}
    </Link>
    <div className="flex items-start justify-between gap-3 pt-3">
      <div className="min-w-0"><p className="truncate text-sm font-medium text-ink">{product.name}</p><p className="mt-1 text-xs text-muted">{product.category} · {product.colour}</p></div>
      <span className="shrink-0 text-sm font-medium text-ink">{money(product.priceCents)}</span>
    </div>
    {product.isNew && <p className="mt-2 text-[0.65rem] font-semibold uppercase tracking-widest text-accent">New arrival</p>}
  </article>;
}

export function ProductGrid({ products }: { products: Product[] }) {
  return <div className="grid grid-cols-2 gap-x-4 gap-y-9 sm:grid-cols-3 lg:grid-cols-4 lg:gap-x-6">{products.map((product) => <ProductCard key={product.id} product={product} />)}</div>;
}

export function LoadState({ error, retry }: { error: string; retry?: () => void }) {
  return <div role="alert" className="rounded-sm border border-border bg-paper px-6 py-10 text-center"><p className="font-display text-2xl text-ink">We couldn’t load the shop</p><p className="mx-auto mt-3 max-w-lg text-sm text-muted">{error}</p>{retry && <button onClick={retry} className="mt-5 rounded-sm bg-ink px-5 py-3 text-sm font-semibold text-paper hover:bg-accent">Try again</button>}</div>;
}

