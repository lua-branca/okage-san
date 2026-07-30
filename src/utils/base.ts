/**
 * BASE_URL対応ヘルパー
 * Astro の base 設定（例: /okage-san/）に合わせてパスを付与します。
 * 本番ドメイン（okage-san.com）時はそのままルートパスとして動作します。
 */
export function withBase(path: string): string {
  if (!path) return path;
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('//') || path.startsWith('#')) {
    return path;
  }
  const base = import.meta.env.BASE_URL.endsWith('/')
    ? import.meta.env.BASE_URL.slice(0, -1)
    : import.meta.env.BASE_URL;
  const cleanPath = path.startsWith('/') ? path : '/' + path;
  return base + cleanPath;
}
