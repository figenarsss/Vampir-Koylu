(function () {
    'use strict';

    const bolum = document.getElementById('bolum');
    if (!bolum) {
        return;
    }

    const YENILEME_MS = 3000;
    let geriSayim = null;

    async function yenile() {
        try {
            const yanit = await fetch(bolum.dataset.url, {
                headers: { 'X-Requested-With': 'fetch' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (yanit.status === 401 || yanit.status === 403 || yanit.status === 404) {
                window.location.reload();
                return;
            }
            if (!yanit.ok) {
                return;
            }

            uygula(await yanit.text());
        } catch (hata) {
            // ağ kesintisinde sonraki tur denenir
        }
    }

    function uygula(html) {
        const sablon = document.createElement('template');
        sablon.innerHTML = html.trim();
        const yeni = sablon.content.firstElementChild;
        if (!yeni) {
            return;
        }

        // imza değişmediyse formlara dokunmadan yalnızca canlı bölümler güncellenir
        const imza = yeni.dataset.imza;
        if (imza !== bolum.dataset.imza) {
            bolum.innerHTML = html;
            bolum.dataset.imza = imza;
        } else {
            yeni.querySelectorAll('[data-canli][id]').forEach(function (kaynak) {
                const hedef = document.getElementById(kaynak.id);
                if (hedef) {
                    hedef.innerHTML = kaynak.innerHTML;
                }
            });
        }
        sayaciBaslat();
    }

    function sayaciBaslat() {
        clearInterval(geriSayim);
        const sayac = document.getElementById('sayac');
        if (!sayac) {
            return;
        }

        let kalan = parseInt(sayac.dataset.kalan, 10);
        geriSayim = setInterval(function () {
            kalan = Math.max(0, kalan - 1);
            sayac.textContent = kalan + ' sn';
            if (kalan === 0) {
                clearInterval(geriSayim);
                setTimeout(yenile, 800);
            }
        }, 1000);
    }

    sayaciBaslat();
    setInterval(yenile, YENILEME_MS);
})();
