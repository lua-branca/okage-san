<?php
/**
 * フォーム設定ファイル（Web非公開領域 okage-san.com/config.php 用サンプル）
 * 
 * 設置先: Xサーバー上の public_html の1つ上の階層 (okage-san.com/config.php)
 * 目的: GAS Web AppのURLおよび認証用SECRET_TOKENを外部から直接閲覧されない安全な場所に配置
 */
return [
    // Google Apps Script でデプロイしたWeb AppのURL
    'GAS_WEB_APP_URL' => 'https://script.google.com/macros/s/YOUR_SCRIPT_ID/exec',

    // GAS側 Code.gs の CONFIG.SECRET_TOKEN と一致させる認証トークン
    // 🔴 実際の値をこのファイルに書かないこと（このリポジトリは PUBLIC です）
    'SECRET_TOKEN'     => 'ここに実際のトークンを入れる',
];
