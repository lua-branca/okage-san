import { getCollection, type CollectionEntry } from 'astro:content';
import { popularSlugs } from '../data/popular';

type Post = CollectionEntry<'blog'>;

/** 記事の並び順。**ここ1か所だけを直せば全ページに効きます**。
 *
 *  🔴 以前はトップ・一覧・記事ページ・サイトマップの4か所で
 *     それぞれ .sort() を書いていたため、直し忘れると並びがズレました。
 *     並び順を変えるときは、必ずこの関数を直してください。
 *
 *  並びのルール（2026-08-07 確定）
 *   ① pubDate（サイト掲載日）が新しい順
 *   ② 同じ日なら originalDate（FB投稿日）が新しい順
 *      → サイト公開前に書き溜めた記事は全部 pubDate が同じになるので、
 *        そのままだとファイル名のアルファベット順という無意味な並びになります。
 *        「元の投稿が新しいものほど上」にして、話の鮮度順に並べます。
 *   ③ originalDate が未記入の記事は最後に回します（0扱い）。
 *      過去記事を掘り起こしたときに先頭を占領させないためです。
 */
export function comparePosts(a: Post, b: Post): number {
  const byPub = +b.data.pubDate - +a.data.pubDate;
  if (byPub !== 0) return byPub;
  return +(b.data.originalDate ?? 0) - +(a.data.originalDate ?? 0);
}

/** 公開中の記事を、上の並び順で返します。下書き（draft: true）は含みません。 */
export async function getSortedPosts(): Promise<Post[]> {
  const posts = await getCollection('blog', ({ data }) => !data.draft);
  return posts.sort(comparePosts);
}

/** ピックアップした記事を、`src/data/popular.ts` に書いた順で返します。
 *
 *  🔴 順位のデータ元は popular.ts の1か所だけです。
 *     GA4の実測に切り替えるとき（Phase 3）も、**このファイルは直しません**。
 *     popular.ts の配列の中身が入れ替わるだけで、表示は自動で追従します。
 *  ⚠️ 存在しない slug・下書きの slug は黙って飛ばします（消した記事が残っていても壊れない）。
 */
export async function getPopularPosts(limit = 3): Promise<Post[]> {
  const all = await getCollection('blog', ({ data }) => !data.draft);
  const byId = new Map(all.map((p) => [p.id, p]));
  return popularSlugs
    .map((slug) => byId.get(slug))
    .filter((p): p is Post => p !== undefined)
    .slice(0, limit);
}

/** ピックアップを先頭に、足りない分を新しい記事で埋めて返します。
 *  トップページの「書いてきたこと」で使っています。
 *  ⚠️ ピックアップと新着が重複しないよう、すでに出した記事は除いています。 */
export async function getPickedThenLatest(limit = 4): Promise<Post[]> {
  const picked = await getPopularPosts(limit);
  if (picked.length >= limit) return picked;
  const pickedIds = new Set(picked.map((p) => p.id));
  const rest = (await getSortedPosts()).filter((p) => !pickedIds.has(p.id));
  return [...picked, ...rest].slice(0, limit);
}
