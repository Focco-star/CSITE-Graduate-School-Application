<?php /* Opens PHP to prepare the background slideshow. */ ?>
<?php
// Starts the list of background image filenames.
$slides = [
    // Adds the first background image filename to the list.
    'slide-1.jpg',
    // Adds the second background image filename to the list.
    'slide-2.jpg',
    // Adds the third background image filename to the list.
    'slide-3.jpg',
    // Adds the fourth background image filename to the list.
    'slide-4.jpg',
    // Adds the fifth background image filename to the list.
    'slide-5.jpg',
    // Adds the sixth background image filename to the list.
    'slide-6.jpg',
// Closes the list of background image filenames.
];
// Closes PHP so the slideshow HTML can be printed.
?>
<?php /* Starts the full-screen background slideshow container. */ ?>
<div class="slideshow-bg" aria-hidden="true">
<?php /*     Loops through each image filename and remembers its position. */ ?>
    <?php foreach ($slides as $i => $file): ?>
<?php /*     Prints one slide; the first slide starts active and shows its image. */ ?>
    <div class="slide<?= $i === 0 ? ' active' : '' ?>" style="background-image:url('<?= asset('img/' . $file) ?>')"></div>
<?php /*     Ends the loop that prints the slides. */ ?>
    <?php endforeach; ?>
<?php /*     Adds a dark overlay so text remains readable over the photos. */ ?>
    <div class="slide-overlay"></div>
<?php /* Closes the slideshow container. */ ?>
</div>
