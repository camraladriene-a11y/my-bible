<?php

session_start();

require_once "db.php";
require_once "translate.php";

// Translating a long chapter can take a while the first time
// (results are cached afterwards).
set_time_limit(120);


// --------------------------------------------------
// CHECK LOGIN
// --------------------------------------------------

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


// --------------------------------------------------
// USER
// --------------------------------------------------

$userName = $_SESSION["user_name"];


// --------------------------------------------------
// BIBLE BOOKS AND THEIR NUMBER OF CHAPTERS
// (first 39 = Old Testament, last 27 = New Testament)
// --------------------------------------------------

$chapterCounts = [
    "Genesis" => 50, "Exodus" => 40, "Leviticus" => 27,
    "Numbers" => 36, "Deuteronomy" => 34,
    "Joshua" => 24, "Judges" => 21, "Ruth" => 4,
    "1 Samuel" => 31, "2 Samuel" => 24,
    "1 Kings" => 22, "2 Kings" => 25,
    "1 Chronicles" => 29, "2 Chronicles" => 36,
    "Ezra" => 10, "Nehemiah" => 13, "Esther" => 10,
    "Job" => 42, "Psalms" => 150, "Proverbs" => 31,
    "Ecclesiastes" => 12, "Song of Solomon" => 8,
    "Isaiah" => 66, "Jeremiah" => 52, "Lamentations" => 5,
    "Ezekiel" => 48, "Daniel" => 12,
    "Hosea" => 14, "Joel" => 3, "Amos" => 9, "Obadiah" => 1,
    "Jonah" => 4, "Micah" => 7, "Nahum" => 3, "Habakkuk" => 3,
    "Zephaniah" => 3, "Haggai" => 2, "Zechariah" => 14, "Malachi" => 4,

    "Matthew" => 28, "Mark" => 16, "Luke" => 24, "John" => 21, "Acts" => 28,
    "Romans" => 16, "1 Corinthians" => 16, "2 Corinthians" => 13,
    "Galatians" => 6, "Ephesians" => 6, "Philippians" => 4, "Colossians" => 4,
    "1 Thessalonians" => 5, "2 Thessalonians" => 3,
    "1 Timothy" => 6, "2 Timothy" => 4, "Titus" => 3, "Philemon" => 1,
    "Hebrews" => 13, "James" => 5,
    "1 Peter" => 5, "2 Peter" => 3,
    "1 John" => 5, "2 John" => 1, "3 John" => 1,
    "Jude" => 1, "Revelation" => 22
];

$books = array_keys($chapterCounts);


// --------------------------------------------------
// BIBLE VERSIONS
// --------------------------------------------------

$versions = [
    "web"   => "World English Bible",
    "kjv"   => "King James Version",
    "asv"   => "American Standard Version",
    "bbe"   => "Bible in Basic English",
    "darby" => "Darby Bible",
    "dra"   => "Douay-Rheims"
];


// --------------------------------------------------
// LANGUAGES
// --------------------------------------------------

$languages = [
    "en"  => "English",
    "tl"  => "Tagalog",
    "war" => "Waray-Waray"
];


// --------------------------------------------------
// GET USER INPUT
// --------------------------------------------------

$language    = $_GET["language"] ?? "en";
$book        = $_GET["book"] ?? "Genesis";
$chapter     = isset($_GET["chapter"]) ? (int) $_GET["chapter"] : 1;
$translation = $_GET["translation"] ?? "web";
$reference   = trim($_GET["reference"] ?? "");


// --------------------------------------------------
// VALIDATE INPUT
// --------------------------------------------------

if (!array_key_exists($language, $languages)) {
    $language = "en";
}

if (!in_array($book, $books)) {
    $book = "Genesis";
}

if ($chapter < 1) {
    $chapter = 1;
}

if ($chapter > $chapterCounts[$book]) {
    $chapter = $chapterCounts[$book];
}

if (!array_key_exists($translation, $versions)) {
    $translation = "web";
}


// --------------------------------------------------
// HELPER: LINK TO A CHAPTER
// --------------------------------------------------

function chapterLink($language, $book, $chapter, $translation)
{
    return "?" . http_build_query([
        "language"    => $language,
        "book"        => $book,
        "chapter"     => $chapter,
        "translation" => $translation
    ]);
}


// --------------------------------------------------
// CREATE BIBLE REFERENCE
// --------------------------------------------------

if ($reference != "") {
    $passage = $reference;
} else {
    $passage = $book . " " . $chapter;
}


// --------------------------------------------------
// BIBLE API
// --------------------------------------------------

$url =
    "https://bible-api.com/"
    . rawurlencode($passage)
    . "?translation="
    . rawurlencode($translation);


// --------------------------------------------------
// GET DATA FROM BIBLE API
// --------------------------------------------------

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, "MyBibleApp/1.0");

$response = curl_exec($ch);

$error = "";

if ($response === false) {
    $error = "Unable to connect to Bible API.";
    $data = null;
} else {
    $data = json_decode($response, true);
}

curl_close($ch);


// --------------------------------------------------
// API ERROR
// --------------------------------------------------

if (isset($data["error"])) {
    $error = $data["error"];
}


// --------------------------------------------------
// PAGE TEXT
// --------------------------------------------------

$text = [

    "en" => [
        "title"        => "My Bible",
        "welcome"      => "Welcome",
        "subtitle"     => "Read, search, and explore the Bible.",
        "search"       => "Search the Bible",
        "placeholder"  => "Example: John 3:16",
        "searchButton" => "Search",
        "version"      => "Bible Version",
        "language"     => "Language",
        "previous"     => "Previous",
        "next"         => "Next",
        "logout"       => "Logout",
        "oldTestament" => "Old Testament",
        "newTestament" => "New Testament",
        "noPassage"    => "No Bible passage found.",
        "unavailable"  => "Translation unavailable right now."
    ],

    "tl" => [
        "title"        => "Aking Bibliya",
        "welcome"      => "Maligayang pagdating",
        "subtitle"     => "Magbasa, maghanap, at tumuklas ng Bibliya.",
        "search"       => "Maghanap sa Bibliya",
        "placeholder"  => "Halimbawa: John 3:16",
        "searchButton" => "Maghanap",
        "version"      => "Bersyon ng Bibliya",
        "language"     => "Wika",
        "previous"     => "Nakaraan",
        "next"         => "Susunod",
        "logout"       => "Mag-logout",
        "oldTestament" => "Lumang Tipan",
        "newTestament" => "Bagong Tipan",
        "noPassage"    => "Walang nahanap na talata ng Bibliya.",
        "unavailable"  => "Hindi available ang salin sa ngayon."
    ],

    "war" => [
        "title"        => "Akon Biblia",
        "welcome"      => "Maupay nga pag-abot",
        "subtitle"     => "Basaha, pamilnga, ngan usisaha an Biblia.",
        "search"       => "Pamiling ha Biblia",
        "placeholder"  => "Pananglitan: John 3:16",
        "searchButton" => "Pamiling",
        "version"      => "Bersyon han Biblia",
        "language"     => "Pinulongan",
        "previous"     => "Naglabay",
        "next"         => "Sunod",
        "logout"       => "Pag-logout",
        "oldTestament" => "Daan nga Katugonan",
        "newTestament" => "Bag-o nga Katugonan",
        "noPassage"    => "Waray nakit-an nga bersikulo han Biblia.",
        "unavailable"  => "Waray pa magamit an hubad yana."
    ]
];

$t = $text[$language];

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo htmlspecialchars($t["title"]); ?></title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="bible-enhance.css">
<link rel="stylesheet" href="bible-book.css">

</head>


<body>


<!-- ==========================================
     NAVIGATION (language + version + logout)
========================================== -->

<nav>

    <div class="nav-logo">📖 My Bible</div>

    <div class="nav-right">

        <span class="nav-welcome">
            👋
            <?php echo htmlspecialchars($t["welcome"]); ?>,
            <strong><?php echo htmlspecialchars($userName); ?></strong>
        </span>

        <form method="GET" class="nav-form">

            <select
                name="language"
                title="<?php echo htmlspecialchars($t["language"]); ?>"
                onchange="this.form.submit()"
            >
                <?php foreach ($languages as $code => $name): ?>
                    <option
                        value="<?php echo htmlspecialchars($code); ?>"
                        <?php if ($language == $code) { echo "selected"; } ?>
                    >
                        🌐 <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select
                name="translation"
                title="<?php echo htmlspecialchars($t["version"]); ?>"
                onchange="this.form.submit()"
            >
                <?php foreach ($versions as $id => $name): ?>
                    <option
                        value="<?php echo htmlspecialchars($id); ?>"
                        <?php if ($translation == $id) { echo "selected"; } ?>
                    >
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="hidden" name="book"
                   value="<?php echo htmlspecialchars($book); ?>">

            <input type="hidden" name="chapter"
                   value="<?php echo $chapter; ?>">

            <?php if ($reference != ""): ?>
                <input type="hidden" name="reference"
                       value="<?php echo htmlspecialchars($reference); ?>">
            <?php endif; ?>

        </form>

        <a href="logout.php" class="logout">
            <?php echo htmlspecialchars($t["logout"]); ?>
        </a>

    </div>

</nav>


<!-- ==========================================
     HERO
========================================== -->

<section class="hero">

    <div>
        <h1><?php echo htmlspecialchars($t["title"]); ?></h1>
        <p><?php echo htmlspecialchars($t["subtitle"]); ?></p>
    </div>

    <div class="hero-icon">✝</div>

</section>


<main>


<!-- ==========================================
     SEARCH
========================================== -->

<section class="search-card">

    <h2>
        🔎
        <?php echo htmlspecialchars($t["search"]); ?>
    </h2>

    <form method="GET" class="search-form">

        <input
            type="text"
            name="reference"
            placeholder="<?php echo htmlspecialchars($t["placeholder"]); ?>"
            value="<?php echo htmlspecialchars($reference); ?>"
        >

        <input type="hidden" name="language"
               value="<?php echo htmlspecialchars($language); ?>">

        <input type="hidden" name="translation"
               value="<?php echo htmlspecialchars($translation); ?>">

        <button type="submit">
            <?php echo htmlspecialchars($t["searchButton"]); ?>
        </button>

    </form>

</section>


<!-- ==========================================
     THE BOOK (left page = books/chapters,
               right page = verses)
========================================== -->

<section class="bible-card">

<div class="book-spread">


    <!-- ================= LEFT PAGE ================= -->

    <aside class="page page-left">

        <?php $bookIndex = 0; ?>

        <?php foreach ($chapterCounts as $bookName => $count): ?>

            <?php if ($bookIndex === 0): ?>
                <h3 class="toc-heading">
                    <?php echo htmlspecialchars($t["oldTestament"]); ?>
                </h3>
            <?php elseif ($bookIndex === 39): ?>
                <h3 class="toc-heading">
                    <?php echo htmlspecialchars($t["newTestament"]); ?>
                </h3>
            <?php endif; ?>

            <details
                class="toc-book"
                <?php if ($reference == "" && $bookName == $book) { echo "open"; } ?>
            >

                <summary><?php echo htmlspecialchars($bookName); ?></summary>

                <div class="chapter-grid">

                    <?php for ($c = 1; $c <= $count; $c++): ?>

                        <a
                            href="<?php echo htmlspecialchars(
                                chapterLink($language, $bookName, $c, $translation)
                            ); ?>"
                            class="chapter-num<?php
                                if (
                                    $reference == "" &&
                                    $bookName == $book &&
                                    $c == $chapter
                                ) {
                                    echo " active";
                                }
                            ?>"
                        ><?php echo $c; ?></a>

                    <?php endfor; ?>

                </div>

            </details>

            <?php $bookIndex++; ?>

        <?php endforeach; ?>

    </aside>


    <!-- ================= RIGHT PAGE ================= -->

    <div class="page page-right">

    <?php if ($error != ""): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>


    <?php elseif ($data && isset($data["verses"])): ?>


        <!-- BIBLE HEADER -->
        <div class="bible-title">

            <div class="cross">✝</div>

            <h2>
                <?php echo htmlspecialchars($data["reference"]); ?>
            </h2>

            <p>
                <?php
                echo htmlspecialchars(
                    $data["translation_name"] ?? $versions[$translation]
                );
                ?>
            </p>

            <span class="language-badge">
                <?php echo htmlspecialchars($languages[$language]); ?>
            </span>

        </div>


        <!-- VERSES -->
        <div class="verses">

        <?php foreach ($data["verses"] as $verse): ?>

            <?php

            $originalVerse = trim($verse["text"]);

            // Reference like "John 3:16" (used for curated translations)
            $verseRef =
                ($verse["book_name"] ?? $book)
                . " " . ($verse["chapter"] ?? $chapter)
                . ":" . $verse["verse"];

            // --------------------------------------------------
            // TRANSLATE VERSE
            // --------------------------------------------------

            $translationFailed = false;

            $translatedVerse =
                translateVerse(
                    $originalVerse,
                    $language,
                    $verseRef,
                    $translationFailed
                );

            ?>

            <div class="verse">

                <span class="verse-number">
                    <?php echo htmlspecialchars($verse["verse"]); ?>
                </span>

                <div class="verse-content">

                    <?php if ($language == "en" || $translationFailed): ?>

                        <!-- ENGLISH (or fallback if translation failed) -->
                        <div class="original-verse">
                            <?php echo htmlspecialchars($originalVerse); ?>
                        </div>

                        <?php if ($translationFailed): ?>
                            <div class="translated-verse">
                                <em><?php echo htmlspecialchars($t["unavailable"]); ?></em>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>

                        <!-- TRANSLATED ONLY (Tagalog or Waray-Waray) -->
                        <div class="translated-verse">
                            <?php echo htmlspecialchars($translatedVerse); ?>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

        </div>


        <!-- PREVIOUS / NEXT CHAPTER -->
        <?php if ($reference == ""): ?>

            <div class="chapter-nav">

                <?php if ($chapter > 1): ?>
                    <a class="chapter-link" href="<?php echo htmlspecialchars(
                        chapterLink($language, $book, $chapter - 1, $translation)
                    ); ?>">← <?php echo htmlspecialchars($t["previous"]); ?></a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <?php if ($chapter < $chapterCounts[$book]): ?>
                    <a class="chapter-link" href="<?php echo htmlspecialchars(
                        chapterLink($language, $book, $chapter + 1, $translation)
                    ); ?>"><?php echo htmlspecialchars($t["next"]); ?> →</a>
                <?php endif; ?>

            </div>

        <?php endif; ?>


    <?php else: ?>

        <!-- EMPTY RESULT -->
        <div class="empty">

            📖

            <p><?php echo htmlspecialchars($t["noPassage"]); ?></p>

        </div>

    <?php endif; ?>

    </div>


</div>

</section>


</main>


<!-- ==========================================
     FOOTER
========================================== -->

<footer>

    My Bible App

    <br>

    Powered by

    <a href="https://bible-api.com/" target="_blank">bible-api.com</a>

</footer>


<script>
// Scroll the left page so the current chapter is in view
(function () {
    var active = document.querySelector(".chapter-num.active");
    var box = document.querySelector(".page-left");

    if (active && box) {
        box.scrollTop = active.offsetTop - box.clientHeight / 2;
    }
})();
</script>


</body>

</html>