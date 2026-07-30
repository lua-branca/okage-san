#!/bin/bash
# ============================================================
# プレビュー公開スクリプト（岡さん確認用）
#
#   ローカルでビルド → main へ push → GitHub Pages が自動更新
#   公開先: https://lua-branca.github.io/okage-san/
#
# 実行場所: site/（このファイルがある場所）
# 使い方:   bash _upload.sh "コミットメッセージ"
#
# 🔴 これは本番（okage-san.com）には上がりません。
#    本番へ上げる仕組みは、まだ意図的に作っていません。
#    切り替え手順 → 20_Site/SITE_SPEC.md 第2章（Obsidian側）
#
# 🔴 このリポジトリは public です。
#    戦略・顧客データ・法務メモをコミットしないこと。
# ============================================================
set -euo pipefail

COMMIT_MSG="${1:-Update site}"

cd "$(dirname "$0")"

if [ ! -f "astro.config.mjs" ]; then
    echo "ERROR: astro.config.mjs が見つかりません（site/ で実行してください）"
    exit 1
fi

if [ ! -d ".git" ]; then
    echo "ERROR: ここは git リポジトリではありません。"
    echo "       サイトのリポジトリは site/ 直下にあります（lua-branca/okage-san）。"
    exit 1
fi

# ── ① base が外れていないか見張る ──
#    base を外したまま push すると、GitHub Pages でリンクと画像が全部壊れます。
#    本番切替の作業中にうっかり push する事故が起きやすいところです。
if ! grep -q "base: '/okage-san'" astro.config.mjs; then
    echo "⚠️  astro.config.mjs の base が '/okage-san' ではありません。"
    echo "    本番切替用の設定のままだと、プレビューのリンクが全部壊れます。"
    read -r -p "    このまま続けますか？ [y/N] " ans
    [ "$ans" = "y" ] || { echo "中止しました。"; exit 1; }
fi

# ── ② 公開してはいけないものが混ざっていないか見張る ──
#    このリポジトリは public です。戦略・顧客データが入ると事故になります。
NG=$(git diff --cached --name-only; git ls-files --others --exclude-standard)
if echo "$NG" | grep -qiE "10_Strategy|30_Operations|40_Marketing|50_AI_Office|CONTENT_DRAFT|MASTER_PLAN|TRAVEL_LAW|顧客|引き継ぎ"; then
    echo "🔴 公開リポジトリに入れてはいけないファイルが含まれています："
    echo "$NG" | grep -iE "10_Strategy|30_Operations|40_Marketing|50_AI_Office|CONTENT_DRAFT|MASTER_PLAN|TRAVEL_LAW|顧客|引き継ぎ" | sed 's/^/    /'
    echo "   これらは Obsidian にのみ置いてください。中止します。"
    exit 1
fi

# ── ③ ビルド（CIが失敗する前に手元で気づく）──
echo "🔨 ビルド中..."
npm run build || { echo "ERROR: ビルド失敗。push を中止します。"; exit 1; }

# ── ④ push ──
git add -A
git commit -m "$COMMIT_MSG" || { echo "変更なし。終了します。"; exit 0; }
git push origin main

echo
echo "✅ push完了。GitHub Actions がプレビューを更新します（30秒ほど）。"
echo "   確認 → https://lua-branca.github.io/okage-san/"
echo "   実行状況 → https://github.com/lua-branca/okage-san/actions"
