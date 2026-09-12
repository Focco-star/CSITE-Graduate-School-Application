<?php
$slides = [
    'slide-1.jpg',
    'slide-2.jpg',
    'slide-3.jpg',
    'slide-4.jpg',
    'slide-5.jpg',
    'slide-6.jpg',
];
?>
<div class="slideshow-bg" aria-hidden="true">
    <?php foreach ($slides as $i => $file): ?>
    <div class="slide<?= $i === 0 ? ' active' : '' ?>" style="background-image:url('<?= asset('img/' . $file) ?>')"></div>
    <?php endforeach; ?>
    <div class="slide-overlay"></div>
</div>
