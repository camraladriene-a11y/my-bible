<?php

// ==================================================
// BIBLE VERSE TRANSLATOR
// Order of attempts:
//   1. Curated verses (your own hand-checked text)   <- best quality
//   2. Google's public endpoint (works well for Tagalog)
//   3. MyMemory (free fallback, supports Waray-Waray)
// Languages: en = English, tl = Tagalog, war = Waray-Waray
// ==================================================

// Set this to your real email: it raises MyMemory's free limit
// from ~5,000 to ~50,000 characters/day.
const MYMEMORY_EMAIL = "you@example.com";

// Hand-checked translations, keyed by reference then language.
// Fill these in from a Bible text you trust (especially Waray).
function curatedVerses()
{
    return [
        // "John 3:16" => [
        //     "tl"  => "...Tagalog text...",
        //     "war" => "...Waray text...",
        // ],
    ];
}


// Generic HTTP GET helper. Returns the body, or null on any failure.
function httpGet($url)
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status !== 200 || $response === "") {
        return null;
    }

    return $response;
}


// Attempt 2: Google's public endpoint
function translateWithGoogle($text, $target)
{
    $url =
        "https://translate.googleapis.com/translate_a/single"
        . "?client=gtx&sl=en&dt=t"
        . "&tl=" . urlencode($target)
        . "&q=" . urlencode($text);

    $response = httpGet($url);
    if ($response === null) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data[0]) || !is_array($data[0])) {
        return null;
    }

    $result = "";
    foreach ($data[0] as $part) {
        if (isset($part[0])) {
            $result .= $part[0];
        }
    }

    return trim($result) === "" ? null : $result;
}


// Attempt 3: MyMemory (max ~500 bytes per request, so split by sentence)
function translateWithMyMemory($text, $target)
{
    $sentences = preg_split('/(?<=[.!?;])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

    // Group sentences into chunks under 450 bytes
    $chunks = [];
    $current = "";
    foreach ($sentences as $sentence) {
        if ($current !== "" && strlen($current . " " . $sentence) > 450) {
            $chunks[] = $current;
            $current = $sentence;
        } else {
            $current = ($current === "") ? $sentence : $current . " " . $sentence;
        }
    }
    if ($current !== "") {
        $chunks[] = $current;
    }

    $result = "";
    foreach ($chunks as $chunk) {
        $url =
            "https://api.mymemory.translated.net/get"
            . "?q=" . urlencode($chunk)
            . "&langpair=" . urlencode("en|" . $target)
            . "&de=" . urlencode(MYMEMORY_EMAIL);

        $response = httpGet($url);
        if ($response === null) {
            return null;
        }

        $data = json_decode($response, true);

        if (
            !is_array($data) ||
            ($data["responseStatus"] ?? 0) != 200 ||
            !isset($data["responseData"]["translatedText"])
        ) {
            return null;
        }

        $piece = $data["responseData"]["translatedText"];

        // MyMemory puts quota/error messages in the text itself
        if (stripos($piece, "MYMEMORY WARNING") !== false) {
            return null;
        }

        $result .= ($result === "" ? "" : " ") . $piece;
    }

    return trim($result) === "" ? null : $result;
}


// Main function. $reference (e.g. "John 3:16") is optional but lets
// you use curated translations. Returns the English text if every
// attempt fails; check $failed if you want to show a notice in the UI.
function translateVerseUncached($text, $language, $reference = null, &$failed = false)
{
    $failed = false;

    if ($language == "en") {
        return $text;
    }

    $supported = ["tl", "war"];
    if (!in_array($language, $supported, true)) {
        return $text;
    }

    // 1. Curated
    $curated = curatedVerses();
    if ($reference !== null && isset($curated[$reference][$language])) {
        return $curated[$reference][$language];
    }

    // 2. Google (skip for Waray: it is likely unsupported there)
    if ($language === "tl") {
        $result = translateWithGoogle($text, "tl");
        if ($result !== null) {
            return $result;
        }
    }

    // 3. MyMemory
    $result = translateWithMyMemory($text, $language);
    if ($result !== null) {
        return $result;
    }

    $failed = true;
    return $text;
}


// Public function used by index.php. Wraps the translator with a small
// file cache (cache/ folder next to this file), so each verse is only
// translated once. This keeps chapters fast and avoids rate limits.
// Failed translations are NOT cached, so they get retried next time.
function translateVerse($text, $language, $reference = null, &$failed = false)
{
    $failed = false;

    if ($language == "en") {
        return $text;
    }

    $dir  = __DIR__ . "/cache";
    $file = $dir . "/" . md5($language . "|" . $text) . ".txt";

    if (is_file($file)) {
        $cached = file_get_contents($file);
        if ($cached !== false && $cached !== "") {
            return $cached;
        }
    }

    $result = translateVerseUncached($text, $language, $reference, $failed);

    if (!$failed) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, $result);
    }

    return $result;
}

?>