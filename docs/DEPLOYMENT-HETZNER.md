# النشر على Hetzner

الموقع كله يعمل على سيرفر واحد بـ Docker Compose، بالملفات الموجودة في مجلد [deploy/](../deploy):

| الخدمة | الوظيفة |
|---|---|
| `caddy` | يستقبل الزوار ويستخرج شهادات HTTPS ويجدّدها تلقائيًا |
| `web` | واجهة المتجر ولوحة الإدارة (Next.js) |
| `api` | الـ API (Laravel على FrankenPHP) |
| `queue` | ينفّذ المهام في الخلفية (إشعارات، بريد...) |
| `scheduler` | المهام المجدولة، ومنها النسخة الاحتياطية الليلية إلى R2 الساعة 03:00 |
| `postgres` | قاعدة البيانات |
| `redis` | الكاش والجلسات والطوابير |

الصور والنسخ الاحتياطية تبقى على Cloudflare R2.

---

## 1. شراء السيرفر

1. ادخل إلى [console.hetzner.com](https://console.hetzner.com) وأنشئ مشروعًا جديدًا.
2. اضغط **Add Server** واختر:
   - **Location:** Falkenstein أو Nuremberg (ألمانيا، الأقرب للجزائر).
   - **Image:** Ubuntu 24.04.
   - **Type:** Shared vCPU → **CX22** (2 vCPU، 4GB RAM) أو أعلى.
   - **SSH keys:** أضف مفتاح جهازك. إن لم يكن عندك مفتاح، شغّل `ssh-keygen -t ed25519` في PowerShell، ثم الصق محتوى الملف `C:\Users\<اسمك>\.ssh\id_ed25519.pub`.
3. اضغط **Create & Buy now** وانسخ عنوان الـ IP.

## 2. تجهيز السيرفر (مرة واحدة)

من PowerShell على جهازك:

```
ssh root@<IP السيرفر>
```

ثم على السيرفر (ضع بريدك الحقيقي):

```
curl -fsSL https://raw.githubusercontent.com/toohat322-hue/chaba/master/deploy/install.sh | bash -s -- you@example.com
```

السكربت يثبّت Docker، ويضبط الجدار الناري، ويضيف ذاكرة swap، وينشئ مستخدمًا اسمه `deploy`، وينزّل الكود إلى `/opt/chaba`، وينشئ ملفّي الإعدادات مع كلمات سر عشوائية.

في آخره يطبع:
- عنوان المتجر وعنوان الـ API. بدون دومين، تكون العناوين بالشكل `https://chaba-1-2-3-4.sslip.io`.
- **مفتاحًا خاصًا** تحتاجه في الخطوة 6. انسخه كاملًا.

## 3. ملفّا الإعدادات

| الملف | المحتوى |
|---|---|
| `/opt/chaba/deploy/.env` | العناوين، كلمة سر قاعدة البيانات، إعدادات بناء الواجهة |
| `/opt/chaba/deploy/api.env` | إعدادات Laravel: R2، البريد، SMS، Google... |

لا تُرفع هذه الملفات إلى GitHub أبدًا. عدّلها بالأمر `nano` (للحفظ اضغط Ctrl+O ثم Enter، وللخروج Ctrl+X).

## 4. ربط Cloudflare R2 (إجباري)

من لوحة Cloudflare ← R2:
1. أنشئ bucket، أو استعمل الموجود.
2. من bucket ← **Settings** ← **Public access**: فعّله وانسخ الرابط العام (`https://pub-xxxx.r2.dev`).
3. من **Manage API Tokens** ← **Create API Token**: اختر صلاحية Object Read & Write على هذا الـ bucket، وانسخ المفتاحين.

في `nano /opt/chaba/deploy/api.env`:

```
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=اسم-الـ-bucket
AWS_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
AWS_URL=https://pub-xxxx.r2.dev
```

وفي `nano /opt/chaba/deploy/.env`:

```
NEXT_PUBLIC_ASSET_URL=https://pub-xxxx.r2.dev
```

## 5. أول تشغيل وحساب الإدارة

```
su - deploy -c "/opt/chaba/deploy/deploy.sh"
su - deploy -c "/opt/chaba/deploy/create-admin.sh"
```

أول تشغيل يستغرق بين 5 و10 دقائق. بعده افتح عنوان المتجر، ولوحة الإدارة على `/ar/adminportal`.

## 6. التحديث التلقائي من GitHub

في GitHub افتح المستودع ← **Settings** ← **Secrets and variables** ← **Actions** ← **New repository secret**، وأضف:

| الاسم | القيمة |
|---|---|
| `HETZNER_HOST` | IP السيرفر |
| `HETZNER_SSH_KEY` | المفتاح الذي طبعه `install.sh`، كاملًا مع سطري BEGIN وEND |

بعد ذلك، كل push إلى `master` يحدّث الموقع تلقائيًا. تتابع التحديثات من تبويب **Actions**، وتقدر تشغّل تحديثًا يدويًا من **Deploy** ← **Run workflow**.

---

## عند شراء دومين

1. في إعدادات DNS للدومين، أضف سجلّي `A` يشيران إلى IP السيرفر: واحد لـ `@`، وواحد لـ `api`.
2. في `/opt/chaba/deploy/.env`:
   ```
   WEB_HOST=chaba.dz
   API_HOST=api.chaba.dz
   ```
3. شغّل `su - deploy -c "/opt/chaba/deploy/deploy.sh"`. يستخرج Caddy الشهادات الجديدة تلقائيًا.
4. إن كان تسجيل الدخول بـ Google مفعّلًا، أضف الرابط الجديد `https://api.<الدومين>/api/v1/auth/google/callback` في Google Cloud Console.

## أوامر مفيدة على السيرفر

كلها تُنفَّذ بعد `cd /opt/chaba/deploy` وبالمستخدم `deploy` (`su - deploy` ثم `cd /opt/chaba/deploy`).

| الغرض | الأمر |
|---|---|
| حالة الخدمات | `docker compose ps` |
| سجلات الـ API (آخر 100 سطر، مع المتابعة) | `docker compose logs -f --tail=100 api` |
| أمر artisan | `docker compose exec api php artisan <الأمر>` |
| نسخة احتياطية الآن | `docker compose exec api php artisan backup:run` |
| إعادة تشغيل كل شيء | `docker compose restart` |
