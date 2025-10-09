<?php
$today = date('d.m.Y');
$now = date('H:i:s'); ?>
</article>
<footer>
  <p>Seite abgerufen am <?=$today?> um <?=$now?> Uhr.</p>
  <button onclick="refreshCSS()">
    Refresh CSS
  </button>
</footer>

<script>
    refreshCSS = () => {
        let links = document.getElementsByTagName('link');
        for (let i = 0; i < links.length; i++) {
            if (links[i].getAttribute('rel') == 'stylesheet') {
                let href = links[i].getAttribute('href')
                                        .split('?')[0];

                let newHref = href + '?version='
                            + new Date().getMilliseconds();

                links[i].setAttribute('href', newHref);
            }
        }
    }
</script>
</body>
</html>

