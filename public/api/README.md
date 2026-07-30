# Webhook の置き場所（Phase 2）

公開URLが必要なので、**サイト公開後**に実装します。

| ファイル | 役割 | 流用元 |
| :--- | :--- | :--- |
| `stripe.php` | 💎 石の入金を自動記録 | 米缶 `webhook.php`（署名検証＋即200返し＋fastcgi_finish_request） |
| `univapay.php` | 🎋 ツアーのカード決済（使い始めたら） | 上と同じ構造 |

Phase 1 は **銀行振込が主軸**なので、入金確認は週2回の手動です。
仕様: `50_Rebuild_2026/30_Operations/BACKEND_ARCHITECTURE.md`
