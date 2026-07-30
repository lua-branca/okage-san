# okage-san.com（マインドフリー神道学アカデミア）

神道家・岡慶行さんのサイト。Astro で構築。

- **プレビュー（岡さん確認用）**: https://lua-branca.github.io/okage-san/
- **本番**: https://okage-san.com （現行WordPress。まだ切り替えていません）

## 動かす

```bash
npm install
npm run dev     # http://localhost:4321/okage-san/
npm run build
```

## 🔴 このリポジトリに入れないもの

**public リポジトリです**（GitHub Pages を無料で使うため）。
戦略・顧客データ・売上・法務メモ・顧客アンケートの原文は、
**絶対にここへコミットしないでください。**

それらは **Obsidian（Googleドライブ）にのみ** あります。gitでは管理していません。
`Projects/Mind_Free_Shinto/50_Rebuild_2026/`

## ドキュメント

Obsidian の `Projects/Mind_Free_Shinto/50_Rebuild_2026/` にあります。

| | |
| :--- | :--- |
| `20_Site/SITE_SPEC.md` | 技術仕様の正本 |
| `20_Site/COMPONENTS.md` | 部品リスト（見た目は `/styleguide/`） |
| `10_Strategy/TRAVEL_LAW_MEMO.md` | 旅行業法。運用を変えるときは必ず読む |

## 崩してはいけないこと

1. **改行文体**: `remark-breaks` を外さない（岡さんの1行13字の改行が文体そのもの）
2. **創作禁止**: 岡さんの言葉を代筆で作らない。埋められない箇所は `（ヒアリング待ち）` と明記
3. **色**: `src/styles/tokens.css` だけで管理。各ページに色コードを書かない
4. **記事本文に `data-reveal` を付けない**（JSオフ時に本文が消える）
5. **効果・効能を保証する表現を書かない**
6. **移動・宿泊の記述**は実際の運用と一致させる（`TRAVEL_LAW_MEMO` 4d章）
