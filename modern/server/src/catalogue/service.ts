const legacyCategories = ['t-shirts', 'shirts', 'hoodies', 'jackets', 'pants', 'accessories'];

export interface CatalogueQuery {
  search: string;
  categories: string[];
  colours: string[];
  minPrice: string | null;
  maxPrice: string | null;
  sort: string;
  page: number;
  limit: number;
}

export interface FilterResult {
  whereSql: string;
  parameters: (string | number)[];
  orderBy: string;
  sortKey: string;
  page: number;
  limit: number;
  notice: string | null;
  validPriceRange: boolean;
  appliedCategories: string[];
  appliedColours: string[];
}

function escapeRegex(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

export function legacyLikePattern(search: string): string {
  let pattern = '';
  for (let index = 0; index < search.length; index += 1) {
    const character = search[index]!;
    if (character === '\\' && index + 1 < search.length) {
      const next = search[index + 1]!;
      pattern += ['!', '%', '_'].includes(next) ? `!${next}` : next;
      index += 1;
    } else if (character === '!') {
      pattern += '!!';
    } else {
      pattern += character;
    }
  }
  return `%${pattern}%`;
}

/** Preserve MySQL LIKE's `%` and `_` search wildcards from the PHP catalogue. */
export function legacyLikeRegex(search: string): RegExp {
  let pattern = '';
  for (let index = 0; index < search.length; index += 1) {
    const character = search[index];
    if (character === '\\' && index + 1 < search.length) {
      pattern += escapeRegex(search[index + 1]);
      index += 1;
    } else if (character === '%') {
      pattern += '.*';
    } else if (character === '_') {
      pattern += '.';
    } else {
      pattern += escapeRegex(character);
    }
  }
  return new RegExp(pattern, 'iu');
}

function normalizeBound(raw: string | null, boundary: 'min' | 'max'): { cents: number | null; invalid: boolean } {
  if (raw === null || raw.trim() === '') return { cents: null, invalid: false };
  const value = Number(raw.trim());
  if (!Number.isFinite(value) || value < 0) return { cents: null, invalid: true };
  if (value > Number.MAX_SAFE_INTEGER / 100) {
    return { cents: boundary === 'min' ? Number.MAX_SAFE_INTEGER : null, invalid: false };
  }
  const cents = value * 100;
  return {
    cents: boundary === 'min' ? Math.ceil(cents - 1e-9) : Math.floor(cents + 1e-9),
    invalid: false,
  };
}

const sortOptions: Record<string, string> = {
  featured: 'p.is_top_selling DESC, p.is_new DESC, p.id ASC',
  newest: 'p.created_at DESC, p.id DESC',
  price_asc: 'p.price ASC, p.id ASC',
  price_desc: 'p.price DESC, p.id DESC',
};

export function buildCatalogueFilter(query: CatalogueQuery, availableColours: string[]): FilterResult {
  const conditions: string[] = [];
  const parameters: (string | number)[] = [];
  const categories = [...new Set(query.categories)].filter((category) => legacyCategories.includes(category));
  const requestedColours = new Set(query.colours.map((colour) => colour.toLocaleLowerCase()));
  const colours = [...new Set(availableColours.filter((colour) => requestedColours.has(colour.toLocaleLowerCase())))];
  if (query.search.trim()) {
    conditions.push("p.name LIKE ? ESCAPE '!'");
    parameters.push(legacyLikePattern(query.search.trim()));
  }
  if (categories.length) {
    conditions.push(`c.slug IN (${categories.map(() => '?').join(',')})`);
    parameters.push(...categories);
  }
  if (colours.length) {
    conditions.push(`p.colour IN (${colours.map(() => '?').join(',')})`);
    parameters.push(...colours);
  }

  const minimum = normalizeBound(query.minPrice, 'min');
  const maximum = normalizeBound(query.maxPrice, 'max');
  let notice: string | null = null;
  if (minimum.invalid) notice = 'Enter a valid minimum price to use that filter.';
  if (maximum.invalid) notice = 'Enter a valid maximum price to use that filter.';
  const validPriceRange = minimum.cents === null || maximum.cents === null || minimum.cents <= maximum.cents;
  if (!validPriceRange) notice = 'Minimum price cannot be greater than maximum price.';
  if (validPriceRange && (minimum.cents !== null || maximum.cents !== null)) {
    if (minimum.cents !== null) {
      conditions.push('p.price >= ?');
      parameters.push(`${Math.floor(minimum.cents / 100)}.${String(minimum.cents % 100).padStart(2, '0')}`);
    }
    if (maximum.cents !== null) {
      conditions.push('p.price <= ?');
      parameters.push(`${Math.floor(maximum.cents / 100)}.${String(maximum.cents % 100).padStart(2, '0')}`);
    }
  }

  return {
    whereSql: conditions.length ? ` WHERE ${conditions.join(' AND ')}` : '',
    parameters,
    orderBy: sortOptions[query.sort] ?? sortOptions.featured,
    sortKey: Object.hasOwn(sortOptions, query.sort) ? query.sort : 'featured',
    page: query.page,
    limit: query.limit,
    notice,
    validPriceRange,
    appliedCategories: categories,
    appliedColours: colours,
  };
}

export function parsePositiveInteger(value: string | null, fallback: number, maximum: number): number | null {
  if (value === null) return fallback;
  if (!/^\d+$/.test(value)) return null;
  const parsed = Number(value);
  return Number.isSafeInteger(parsed) && parsed >= 1 && parsed <= maximum ? parsed : null;
}

export function colourHex(colour: string): string {
  const palette: Record<string, string> = {
    black: '#242424', blue: '#527faf', brown: '#704b39', 'coral orange': '#df785b', cream: '#e9dcc4',
    green: '#54805a', grey: '#8b9295', maroon: '#752b42', 'navy blue': '#293c5d', 'olive green': '#727950',
    pink: '#d988aa', red: '#ad3f43', 'rust brown': '#9b593d', 'teal blue': '#347f94', 'teal green': '#367e71',
    white: '#fafafa', yellow: '#d2b341', navy: '#293c5d', coral: '#d98875', olive: '#727950', ink: '#303a43',
    mist: '#bfc8c7', sand: '#c7ad87', slate: '#68757f', stone: '#b1aa9c', sage: '#9ba88d', 'light blue': '#a4c6df',
  };
  return palette[colour.trim().toLowerCase()] ?? '#929b93';
}

export function roundLegacy(value: number): number {
  return Math.floor(value + 0.5);
}
