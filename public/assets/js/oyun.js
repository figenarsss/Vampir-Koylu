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
            const taslak = sohbetGirdisi();
            const metin = taslak ? taslak.value : '';
            const odakta = taslak !== null && document.activeElement === taslak;
            bolum.innerHTML = html;
            bolum.dataset.imza = imza;
            const yeniGirdi = sohbetGirdisi();
            if (yeniGirdi && metin !== '') {
                yeniGirdi.value = metin;
                if (odakta) {
                    yeniGirdi.focus();
                }
            }
            sohbetiKaydir(true);
        } else {
            const liste = document.getElementById('sohbet-liste');
            const dipteydi = liste ? liste.scrollHeight - liste.scrollTop - liste.clientHeight < 40 : false;
            yeni.querySelectorAll('[data-canli][id]').forEach(function (kaynak) {
                const hedef = document.getElementById(kaynak.id);
                if (hedef) {
                    hedef.innerHTML = kaynak.innerHTML;
                }
            });
            sohbetiKaydir(dipteydi);
        }
        sayaciBaslat();
    }

    function sohbetGirdisi() {
        return bolum.querySelector('.sohbet-form input[name="mesaj"]');
    }

    function sohbetiKaydir(zorla) {
        const liste = document.getElementById('sohbet-liste');
        if (liste && zorla) {
            liste.scrollTop = liste.scrollHeight;
        }
    }

    // Mesaj gönderme: sayfayı yenilemeden gönderir; hata olursa formun altında gösterir
    bolum.addEventListener('submit', async function (olay) {
        const form = olay.target;
        if (!form.classList || !form.classList.contains('sohbet-form')) {
            return;
        }
        olay.preventDefault();
        const girdi = form.querySelector('input[name="mesaj"]');
        const hata = document.getElementById('sohbet-hata');
        if (!girdi || girdi.value.trim() === '') {
            return;
        }
        try {
            const yanit = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'fetch' },
                credentials: 'same-origin',
            });
            if (yanit.status === 401) {
                window.location.reload();
                return;
            }
            let veri = {};
            try {
                veri = await yanit.json();
            } catch (e) {
                // JSON değilse genel hata gösterilir
            }
            if (yanit.ok && veri.ok) {
                girdi.value = '';
                if (hata) {
                    hata.textContent = '';
                }
            } else if (hata) {
                hata.textContent = veri.error || 'Mesaj gönderilemedi.';
            }
            await yenile();
            sohbetiKaydir(true);
        } catch (e) {
            if (hata) {
                hata.textContent = 'Bağlantı sorunu; mesaj gönderilemedi.';
            }
        }
    });

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
    sohbetiKaydir(true);
    setInterval(yenile, YENILEME_MS);
})();
