<?php
$GLOBALS['i18n'] = [];
$GLOBALS['i18n_lang'] = 'en';

function i18nInit(string $lang = 'en'): void {
    $available = array_keys(getAvailableLangs());

    if (isset($_GET['lang']) && in_array($_GET['lang'], $available, true)) {
        $lang = $_GET['lang'];
        setcookie('lang', $lang, time() + 60 * 60 * 24 * 365, '/');
    } elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $available, true)) {
        $lang = $_COOKIE['lang'];
    } else {
        $accepted = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        foreach ($available as $code) {
            if (stripos($accepted, $code) !== false) {
                $lang = $code;
                break;
            }
        }
    }

    $GLOBALS['i18n_lang'] = $lang;
    $compiled = PROJECT_ROOT . '/locales/' . $lang . '/LC_MESSAGES/messages.php';
    if (!file_exists($compiled)) {
        i18nCompile($lang);
    }
    if (file_exists($compiled)) {
        $GLOBALS['i18n']['messages'] = require $compiled;
    }
}

function __(string $msgid, string $domain = 'messages'): string {
    return $GLOBALS['i18n'][$domain][$msgid] ?? $msgid;
}

function __n(string $singular, string $plural, int $n, string $domain = 'messages'): string {
    $key = $n === 1 ? $singular : $plural;
    return $GLOBALS['i18n'][$domain][$key] ?? $key;
}

function getLang(): string {
    return $GLOBALS['i18n_lang'] ?? 'en';
}

function getAvailableLangs(): array {
    return ['en' => 'English', 'id' => 'Bahasa Indonesia'];
}

function i18nCompile(string $lang): void {
    $poFile = PROJECT_ROOT . '/locales/' . $lang . '/LC_MESSAGES/messages.po';
    $phpFile = PROJECT_ROOT . '/locales/' . $lang . '/LC_MESSAGES/messages.php';
    if (!file_exists($poFile)) {
        return;
    }
    $lines = file($poFile, FILE_IGNORE_NEW_LINES);
    $translations = [];
    $msgid = null;
    $msgstr = null;
    $readingMsgid = false;
    $readingMsgstr = false;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            if ($msgid !== null && $msgstr !== null && $msgid !== '') {
                $translations[$msgid] = $msgstr;
            }
            $msgid = null;
            $msgstr = null;
            $readingMsgid = false;
            $readingMsgstr = false;
            continue;
        }
        if (strncmp($line, 'msgid ', 6) === 0) {
            if ($msgid !== null && $msgstr !== null && $msgid !== '') {
                $translations[$msgid] = $msgstr;
            }
            $msgid = i18nUnquote(substr($line, 6));
            $msgstr = null;
            $readingMsgid = true;
            $readingMsgstr = false;
        } elseif (strncmp($line, 'msgstr ', 7) === 0) {
            $msgstr = i18nUnquote(substr($line, 7));
            $readingMsgid = false;
            $readingMsgstr = true;
        } elseif ($line[0] === '"') {
            $part = i18nUnquote($line);
            if ($readingMsgstr && $msgstr !== null) {
                $msgstr .= $part;
            } elseif ($readingMsgid && $msgid !== null) {
                $msgid .= $part;
            }
        }
    }
    if ($msgid !== null && $msgstr !== null && $msgid !== '') {
        $translations[$msgid] = $msgstr;
    }

    $export = "<?php return " . var_export($translations, true) . ";\n";
    file_put_contents($phpFile, $export);
}

function i18nUnquote(string $str): string {
    $str = trim($str);
    if (strlen($str) >= 2 && $str[0] === '"' && $str[strlen($str) - 1] === '"') {
        $str = substr($str, 1, -1);
    }
    return stripcslashes($str);
}
