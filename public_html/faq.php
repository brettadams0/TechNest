<?php
// FAQ page for TechNest
require_once __DIR__ . '/../includes/header.php';
?>
<main>
    <h1>Frequently Asked Questions</h1>
    <dl>
        <dt>How do I create an account?</dt>
        <dd>Click Register in the menu and fill out the form.</dd>
        <dt>How do I reset my password?</dt>
        <dd>Contact support@technest.com for password assistance.</dd>
        <dt>What payment methods are accepted?</dt>
        <dd>We accept major credit cards and PayPal (coming soon).</dd>
        <dt>How do I contact support?</dt>
        <dd>Email us at support@technest.com or use the contact form.</dd>
    </dl>
    <!-- Sample intro video for multimedia requirement -->
    <section>
        <h2>Watch Our Intro Video</h2>
        <video controls width="480">
            <source src="assets/intro.mp4" type="video/mp4">
            Sorry, your browser does not support embedded videos.
        </video>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 