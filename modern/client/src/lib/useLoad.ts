import { useEffect, useState } from 'react';

export function useLoad<T>(loader: () => Promise<T>, dependencies: unknown[] = []) {
  const [data, setData] = useState<T | null>(null);
  const [error, setError] = useState('');
  const [retry, setRetry] = useState(0);
  useEffect(() => {
    let alive = true;
    setError(''); setData(null);
    loader().then((value) => { if (alive) setData(value); }).catch((reason: unknown) => { if (alive) setError(reason instanceof Error ? reason.message : 'Please try again shortly.'); });
    return () => { alive = false; };
  // The pages define request identity through dependencies.
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [...dependencies, retry]);
  return { data, error, retry: () => setRetry((value) => value + 1) };
}
