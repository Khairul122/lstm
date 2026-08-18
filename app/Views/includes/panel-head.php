<?php

declare(strict_types=1);
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e((string) ($title ?? 'Panel')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(base_url('/public/css/panel.css')) ?>?v=<?= time() ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const ssName = urlParams.get('ss_name');
    if (ssName) {
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (typeof html2canvas !== 'undefined') {
                    html2canvas(document.body, {
                        windowWidth: 1920,
                        windowHeight: 1080,
                        scale: 1,
                        useCORS: true,
                        logging: false
                    }).then(function(canvas) {
                        const imgData = canvas.toDataURL('image/png');
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '<?= base_url("/save-screenshot") ?>';
                        
                        const inputName = document.createElement('input');
                        inputName.type = 'hidden';
                        inputName.name = 'name';
                        inputName.value = ssName;
                        
                        const inputImage = document.createElement('input');
                        inputImage.type = 'hidden';
                        inputImage.name = 'image';
                        inputImage.value = imgData;
                        
                        form.appendChild(inputName);
                        form.appendChild(inputImage);
                        document.body.appendChild(form);
                        form.submit();
                    });
                }
            }, 1000);
        });
    }
})();
</script>
