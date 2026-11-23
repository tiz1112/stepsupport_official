<?php
// 「譲渡の相談窓口」フォーム(事業承継・M&Aのページ)の送信先です。
// このファイルはサーバーの中で動くだけで、届け先のメールアドレスがページに出ることはありません。
// 動かすには、PHPが使えるサーバー(一般的なレンタルサーバー)に index.html と同じ場所へ置いてください。

const TO      = 't-izuhara@try-support.com';  // 届け先
const FROM    = 'no-reply@try-support.com';   // 差出人。サイトと同じドメインにすると、迷惑メールに入りにくくなります
const SUBJECT = '【譲渡のご相談】サイトのフォームから';
const BACK    = './#biz-succession';          // 送信のあと「戻る」で開くページ

mb_language('Japanese');
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tokyo');

function post_text(string $key, int $max): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) {
        return '';
    }
    return mb_substr(trim(str_replace("\0", '', $v)), 0, $max);
}

function finish(string $title, string $message, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $m = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $b = htmlspecialchars(BACK, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{$t}｜ステップサポート</title>
<style>
body{margin:0;background:#F1F3F2;color:#1C2430;font-family:"BIZ UDPGothic","Hiragino Sans",sans-serif;line-height:1.9}
main{max-width:560px;margin:0 auto;padding:18vh 24px 80px}
h1{margin:0;font-size:28px;line-height:1.4}
p{margin:18px 0 0;font-size:16px}
a{color:inherit;font-weight:700;text-underline-offset:6px}
</style>
</head>
<body>
<main>
<h1>{$t}</h1>
<p>{$m}</p>
<p><a href="{$b}">事業承継・M&amp;Aのページへ戻る</a></p>
</main>
</body>
</html>
HTML;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . BACK);
    exit;
}

$done = '2営業日以内に、担当者からご連絡します。';

// ロボット対策: 人には見えない欄に入力があるときは、送ったことにして終える
if (post_text('extra', 200) !== '') {
    finish('相談を送りました', $done);
}

$contact = post_text('contact', 200);
$name    = post_text('name', 100);
$message = post_text('message', 4000);
$agree   = post_text('agree', 10) !== '';

if ($contact === '' || !$agree) {
    finish('送れませんでした', 'ご連絡先の入力と、個人情報の取り扱いへの同意が必要です。前のページに戻って、もう一度お送りください。', 400);
}

$body = "サイトの「譲渡の相談窓口」フォームから届きました。\n\n"
      . 'ご連絡先: ' . $contact . "\n"
      . 'お名前: ' . ($name === '' ? '(なし)' : $name) . "\n\n"
      . "ご相談の内容:\n" . ($message === '' ? '(なし)' : $message) . "\n\n"
      . '送信日時: ' . date('Y年n月j日 H:i') . "\n";

$headers = 'From: ' . FROM;
// ご連絡先がメールアドレスなら、このメールにそのまま返信できるようにする
if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
    $headers .= "\r\nReply-To: " . $contact;
}

if (!mb_send_mail(TO, SUBJECT, $body, $headers)) {
    finish('送れませんでした', '時間をおいて、もう一度お送りください。', 500);
}
finish('相談を送りました', $done);
