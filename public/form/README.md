# PHP の置き場所

ここに置いたファイルは、Astroのビルドで `dist/form/` にコピーされ、
FTP同期でXサーバーに一緒に上がります（十郷と同じ形）。

## 作るもの（Phase 1）

| ファイル | 役割 | 流用元 |
| :--- | :--- | :--- |
| `tour.php` | 神遊びの申込フォーム | 米缶 `form.php` |
| `confirm.php` | 確認画面 | 米缶 `confirm.php`（ほぼそのまま） |
| `complete.php` | 完了＋GASへPOST＋自動返信メール | 米缶 `complete.php` |

流用元: `Projects/Jugo/jugo-japan/site/public/kome-can/`
仕様: `50_Rebuild_2026/20_Site/CONTENT_DRAFT/07_申込フォーム.md`

## 🔴 忘れないこと

- 入力項目に **「お振込名義（カタカナ）」を必須**で入れる（入金の突合ができなくなる）
- 自動返信に **振込先＋7日以内の期限**を明記
- `config.php`（GASのURL・共有シークレット）は **public_html の外**に置く。ここには置かない
