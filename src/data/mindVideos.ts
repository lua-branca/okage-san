/** /mind/ の「使い方と瞑想」で見せる、岡さんの解説動画。
 *
 *  🔴 パワーストーンは買って終わりではありません。むしろ買ったあとに
 *     「どう持つのか」「瞑想が続かない」で止まります。ここがその受け皿です。
 *
 *  ── 動画の追加のしかた ──
 *  1. YouTube に「限定公開（Unlisted）」でアップする
 *     ※「非公開（Private）」だと埋め込んでも本人以外は見られません。必ず「限定公開」で。
 *  2. URL の後半（https://youtu.be/**ここ**）を youtubeId に貼る
 *  3. これだけで公開されます。ページ側の修正は不要です
 *
 *  youtubeId が空のものは「準備中」と表示されます（リンクは出ません）。
 *  全部空なら、動画の枠ごと出ずに案内文だけになります。
 *
 *  access:
 *    'public' … 誰でも見られる。ページに埋め込みます
 *    'buyer'  … パワーストーンをお求めいただいた方だけ。ページには題名だけ出して、
 *               動画そのものは購入後のメールでお送りします
 *  🔴 どちらにするかは岡さん・福田さんの判断です。迷ったら 'public' のままにせず確認すること。
 */
export type MindVideo = {
  title: string;
  note: string;
  youtubeId?: string;
  access: 'public' | 'buyer';
  /** 尺の目安。台本は 20_Site/CONTENT_DRAFT/09_石の使い方動画_台本.md */
  length?: string;
};

export const mindVideos: MindVideo[] = [
  {
    title: 'パワーストーンの持ち方、置き方',
    note: '手のどこで持つか。使わないときはどこに置くか。',
    access: 'public',
    length: '3分',
  },
  {
    title: '瞑想のはじめ方',
    note: '座り方、呼吸、意識の置きどころ。はじめての方はここから。',
    access: 'public',
    length: '8分',
  },
  {
    title: '雑念が浮かぶとき',
    note: 'うまくいかない日があって当たり前です。そんな日の過ごし方。',
    access: 'public',
    length: '6分',
  },
  {
    title: 'パワーストーンと共鳴する',
    note: '愛を持つ、感謝を持つ。パワーストーンと一緒に、自分の神性を開いていく時間。',
    access: 'buyer',
    length: '10分',
  },
];
