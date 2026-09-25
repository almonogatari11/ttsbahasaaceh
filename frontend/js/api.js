// Helper untuk memanggil endpoint backend PHP.
// Path relatif karena frontend & backend satu origin (lihat catatan Deployment).

(function (global) {
  function resolveApiBase(currentUrl = global.location?.href || "") {
    try {
      const locationUrl = new URL(currentUrl, global.location?.href || "http://localhost/");
      const pathname = locationUrl.pathname || "/";
      const frontendIndex = pathname.indexOf("/frontend/");

      if (frontendIndex !== -1) {
        return `${pathname.slice(0, frontendIndex)}/backend/api`;
      }

      return "/backend/api";
    } catch (e) {
      return "/backend/api";
    }
  }

  const API_BASE = resolveApiBase();

  function getApiUrl(path) {
    return `${API_BASE}${path}`;
  }

  let botChallengeWarmup = null;

  function isChallengeHtml(rawText) {
    return typeof rawText === 'string' && rawText.includes('/aes.js') && rawText.includes('document.cookie');
  }

  function warmupBotChallenge(url = '/') {
    if (botChallengeWarmup) {
      return botChallengeWarmup;
    }

    botChallengeWarmup = new Promise((resolve) => {
      const iframe = document.createElement('iframe');
      iframe.style.width = '1px';
      iframe.style.height = '1px';
      iframe.style.border = '0';
      iframe.style.position = 'absolute';
      iframe.style.left = '-9999px';
      iframe.style.top = '-9999px';
      iframe.src = url;

      const cleanUp = () => {
        if (iframe.parentNode) {
          iframe.parentNode.removeChild(iframe);
        }
        resolve();
      };

      iframe.onload = () => {
        setTimeout(cleanUp, 5000);
      };
      iframe.onerror = () => {
        setTimeout(cleanUp, 5000);
      };

      document.body.appendChild(iframe);
      setTimeout(cleanUp, 12000);
    });

    return botChallengeWarmup;
  }

  async function apiCall(path, { method = "GET", body = null } = {}) {
    const options = {
      method,
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin", // pastikan cookie session PHP ikut terkirim
    };
    if (body !== null) {
      options.body = JSON.stringify(body);
    }
    async function tryFetch() {
      try {
        return await fetch(getApiUrl(path), options);
      } catch (e) {
        return null;
      }
    }

    // Catatan: sebelumnya di sini selalu ada warmup wajib (nunggu 5-12 detik)
    // sebelum request pertama dikirim, dibuat untuk InfinityFree yang punya
    // challenge anti-bot. Di hosting seperti Railway yang tidak punya
    // challenge itu, ini cuma menambah delay tanpa guna di SETIAP navigasi
    // halaman. Sekarang cukup coba request langsung dulu; kalau ternyata
    // responsnya memang halaman challenge, baru warmup+retry (logika di
    // bawah sudah menangani ini secara reaktif untuk kedua platform).

    // Coba panggil API sekali
    let res = await tryFetch();
    let data = null;

    // Jika respons bukan JSON (sering karena challenge/anti-bot), lakukan "warm-up"
    // dengan memuat hidden iframe ke hello.php, lalu coba ulang panggilan API sekali lagi.
    const processResponse = async (response) => {
      if (!response) return { success: false, message: 'Tidak dapat menghubungi server.' };
      const contentType = response.headers.get("content-type") || "";
      try {
        if (contentType.includes("application/json")) {
          return await response.json();
        }
        const rawText = await response.text();
        const preview = rawText.replace(/\s+/g, " ").slice(0, 220);
        if (isChallengeHtml(rawText)) {
          return {
            success: false,
            message: `Server mengembalikan challenge anti-bot (status ${response.status}). Memuat ulang session...`,
            challenge: true,
            preview,
          };
        }
        return {
          success: false,
          message: `Server mengembalikan respons non-JSON (status ${response.status}). Detail: ${preview}`,
        };
      } catch (e) {
        return { success: false, message: 'Respons server tidak valid.' };
      }
    };

    data = await processResponse(res);

    if (data && data.success === false && data.challenge) {
      await warmupBotChallenge();
      res = await tryFetch();
      data = await processResponse(res);

      if (data && data.success === false && data.challenge) {
        await warmupBotChallenge();
        res = await tryFetch();
        data = await processResponse(res);
      }
    }

    if (res && !res.ok && data && data.success === undefined) {
      data.success = false;
    }

    return data;
  }

  function tampilkanError(pesan) {
    Swal.fire({
      icon: "error",
      title: "Terjadi kesalahan",
      text: pesan,
      confirmButtonColor: "#2563eb",
    });
  }

  function tampilkanSukses(pesan) {
    Swal.fire({
      icon: "success",
      title: pesan,
      confirmButtonColor: "#2563eb",
      timer: 1400,
      showConfirmButton: false,
    });
  }

  global.API_BASE = API_BASE;
  global.getApiUrl = getApiUrl;
  global.apiCall = apiCall;
  global.tampilkanError = tampilkanError;
  global.tampilkanSukses = tampilkanSukses;
  global.resolveApiBase = resolveApiBase;

  if (typeof module !== "undefined" && module.exports) {
    module.exports = { resolveApiBase, getApiUrl, apiCall, tampilkanError, tampilkanSukses };
  }
})(typeof window !== "undefined" ? window : globalThis);
