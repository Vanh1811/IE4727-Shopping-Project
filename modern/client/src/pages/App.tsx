import { Link, Route, Routes } from 'react-router-dom';

function HomePage() {
  return (
    <main className="mx-auto flex min-h-[70vh] w-full max-w-container items-center px-6 py-16">
      <section className="grid w-full items-center gap-12 md:grid-cols-[1fr_0.85fr]">
        <div>
          <p className="mb-5 text-xs font-semibold uppercase tracking-[0.18em] text-accent">
            F2B SHOP / The everyday edit
          </p>
          <h1 className="font-display text-6xl leading-[0.98] tracking-[-0.045em] text-ink sm:text-7xl">
            Wear your <span className="italic text-accent">style.</span>
          </h1>
          <p className="mt-6 max-w-xl text-lg leading-8 text-muted">
            Modern essentials and expressive layers for the way you move, made to help every outfit feel like your own.
          </p>
          <div className="mt-8 flex flex-wrap items-center gap-6">
            <Link className="rounded-sm bg-ink px-6 py-3 text-sm font-semibold text-paper transition hover:bg-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" to="/shop">
              Shop now <span aria-hidden="true">→</span>
            </Link>
            <a className="text-sm font-semibold text-ink underline decoration-border underline-offset-4 hover:decoration-accent" href="#about">
              Explore the edit <span aria-hidden="true">↓</span>
            </a>
          </div>
          <p className="mt-12 text-sm text-muted">Modern storefront foundation · catalogue connection arrives in Stage 2.</p>
        </div>
        <div className="relative overflow-hidden rounded-editorial bg-surface">
          <img className="aspect-[4/5] w-full object-cover" src="/images/branding/hero-fashion.jpg" alt="Model in a neutral outfit seated beside a studio window" />
          <div className="absolute inset-x-0 bottom-0 flex items-end justify-between bg-gradient-to-t from-ink/65 to-transparent p-6 pt-16 text-paper">
            <span className="text-[0.65rem] font-semibold uppercase tracking-[0.18em]">F2B SHOP / 01</span>
            <span className="font-display text-xl italic">Everyday, reimagined.</span>
          </div>
        </div>
      </section>
    </main>
  );
}

function PlaceholderPage({ title }: { title: string }) {
  return (
    <main className="mx-auto min-h-[70vh] w-full max-w-container px-6 py-16">
      <p className="text-xs font-semibold uppercase tracking-[0.18em] text-accent">F2B SHOP</p>
      <h1 className="mt-3 font-display text-5xl text-ink">{title}</h1>
      <p className="mt-5 text-muted">This page will be connected to the verified legacy catalogue in a later stage.</p>
    </main>
  );
}

export default function App() {
  return (
    <div className="flex min-h-screen flex-col bg-background text-text">
      <a className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-paper focus:p-3" href="#main">
        Skip to main content
      </a>
      <header className="sticky top-0 z-20 border-b border-border/80 bg-paper/90 backdrop-blur">
        <div className="mx-auto flex min-h-20 max-w-container items-center justify-between px-6">
          <Link className="flex items-center gap-3 font-semibold tracking-wide text-ink" to="/">
            <span className="grid size-10 place-items-center rounded-full bg-ink text-xs text-paper">F2B</span>
            <span>F2B SHOP</span>
          </Link>
          <nav aria-label="Primary navigation" className="flex items-center gap-6 text-sm">
            <Link className="hover:text-accent" to="/shop">Shop</Link>
            <Link className="hover:text-accent" to="/cart">Bag</Link>
            <Link className="hover:text-accent" to="/orders">Account</Link>
          </nav>
        </div>
      </header>
      <div id="main" className="flex-1">
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/shop" element={<PlaceholderPage title="Shop" />} />
          <Route path="/cart" element={<PlaceholderPage title="Your bag" />} />
          <Route path="/orders" element={<PlaceholderPage title="Order status" />} />
          <Route path="*" element={<PlaceholderPage title="Page not found" />} />
        </Routes>
      </div>
      <footer id="about" className="border-t border-border bg-ink text-paper">
        <div className="mx-auto grid max-w-container gap-8 px-6 py-10 sm:grid-cols-2">
          <div>
            <p className="font-semibold tracking-wide">F2B SHOP</p>
            <p className="mt-2 max-w-md text-sm text-paper/75">Thoughtful essentials and expressive pieces for every version of you.</p>
          </div>
          <div className="sm:text-right">
            <p className="text-sm text-paper/75">Modern Version — parallel to the PHP/MySQL Base Version.</p>
            <p className="mt-2 text-xs text-paper/60">No payment processing is connected.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
