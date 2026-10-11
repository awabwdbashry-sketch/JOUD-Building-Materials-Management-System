<div align="center">

# 🏗️ JOUD — Building Materials Management System

### نظام إدارة مواد البناء

**جود لمواد البناء** · نظام عربي (RTL) لإدارة المبيعات والمخزون والمشتريات والموردين والمصروفات والتقارير

<br>

🌐 **[GitHub Portfolio · awabdev.byethost12.com](https://awabdev.byethost12.com)**
<!-- ضع هنا رابط موقع الـ Portfolio الشخصي إن وُجد -->

<br>

**Software Developer**
**Web & Flutter Developer**
**AI Developer**
**Building Modern Web, Mobile & AI Applications**

<br>

<img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/MySQL%20%2F%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL / MariaDB">
<img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
<img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5">
<img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3">
<img src="https://img.shields.io/badge/Bootstrap-5.3%20RTL-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 5.3 RTL">
<img src="https://img.shields.io/badge/Chart.js-4.4-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white" alt="Chart.js">
<img src="https://img.shields.io/badge/TCPDF-6.7.5-C0392B?style=for-the-badge" alt="TCPDF">
<img src="https://img.shields.io/badge/WAMP-Windows-F58025?style=for-the-badge" alt="WAMP">

</div>

---

<a id="arabic"></a>

# 🇸🇩 العربية

> 🇬🇧 *English documentation starts [below](#english).*

## 📑 المحتويات

1. [عن المشروع](#ar-about)
2. [المميزات الرئيسية](#ar-features)
3. [المبيعات ونقطة البيع](#ar-sales)
4. [المنتجات والمخزون](#ar-inventory)
5. [المشتريات والموردون](#ar-purchases)
6. [العملاء والمدفوعات والديون](#ar-customers)
7. [المصروفات](#ar-expenses)
8. [التقارير ولوحة التحكم](#ar-reports)
9. [المستخدمون والأدوار والصلاحيات](#ar-roles)
10. [إعدادات النظام](#ar-settings)
11. [سجل العمليات](#ar-audit)
12. [الطباعة والتصدير (PDF / CSV)](#ar-export)
13. [التقنيات المستخدمة](#ar-tech)
14. [هيكل المشروع](#ar-tree)
15. [قاعدة البيانات](#ar-db)
16. [ملفات SQL والترحيلات](#ar-sql)
17. [التثبيت والتشغيل على WAMP](#ar-install)
18. [الأمان والخصوصية](#ar-security)
19. [الاختبارات وحالة المشروع](#ar-tests)
20. [النشر والقيود المعروفة](#ar-limits)
21. [المطوّر](#ar-dev)

---

<a id="ar-about"></a>

## 📌 عن المشروع

**JOUD (جود لمواد البناء)** نظام ويب مكتوب بـ **PHP و MySQL/MariaDB** لإدارة محل أو شركة لبيع **مواد البناء**. واجهته عربية بالكامل بتخطيط **RTL**، ويعمل **محليًا على WAMP بدون اتصال بالإنترنت**: مكتبات Bootstrap RTL والأيقونات و Chart.js وخط Cairo و TCPDF كلها موجودة داخل المشروع نفسه ولا تُحمَّل من شبكات خارجية.

يغطي النظام دورة العمل اليومية كاملة: البيع من شاشة نقطة البيع، تتبّع المخزون وحركاته، الشراء من الموردين وتسجيل دفعاتهم، ديون العملاء وسدادها، المصروفات، التقارير مع تصديرها، وسجل تدقيق لكل العمليات الحساسة.

| البند | القيمة |
|---|---|
| اسم المشروع | JOUD — جود لمواد البناء |
| اللغة والاتجاه | العربية (RTL) |
| لغة الخادم | PHP 8.1 أو أحدث (تم التحقق بفحص الصياغة على PHP 8.3) |
| قاعدة البيانات | MySQL / MariaDB — اسمها الافتراضي `joud_building` |
| بيئة التطوير المحلية | WAMP على Windows |
| الأدوار الجاهزة | المدير (`admin`) والكاشير (`cashier`) |
| إصدار المخطط الحالي | `schema_version = 3` |
| المستودع | [JOUD-Building-Materials-Management-System](https://github.com/awabwdbashry-sketch/JOUD-Building-Materials-Management-System) |

---

<a id="ar-features"></a>

## ✨ المميزات الرئيسية

- 🧾 **نقطة بيع (POS)** مع بيع نقدي وآجل وجزئي، وخصومات، ورمز فريد لكل فاتورة يمنع تكرارها عند إعادة الإرسال.
- 📦 **منتجات ومخزون** بحد أدنى للسعر وحد أدنى للمخزون، وسجل كامل لحركات المخزون (قبل/بعد) وتسويات يدوية.
- 🚚 **مشتريات وموردون** مع سجل دفعات للموردين (عند الشراء، لاحقة، أو استرداد) وأرصدة محسوبة من السجل.
- 👥 **عملاء وديون:** رصيد العميل = إجمالي الفواتير غير الملغاة − مجموع دفعاته، وتُوزَّع الدفعات على أقدم الفواتير أولًا.
- 💸 **مصروفات** بتصنيفات وإمكانية إلغاء المصروف.
- 📊 **15 تقريرًا** مع فلاتر، وتصدير **PDF و CSV**، ولوحة تحكم برسوم بيانية.
- 🔐 **أدوار وصلاحيات دقيقة (31 صلاحية)** تُقرأ من القاعدة في كل طلب.
- 📝 **سجل عمليات (Audit Log)** يسجّل العمليات الحساسة ومحاولات الدخول الفاشلة.
- 🖨️ **فواتير PDF حقيقية** بمقاس **A4** وإيصال حراري **80mm** عبر TCPDF.
- 🔁 **ترقية تلقائية للقاعدة** من الإصدارات القديمة دون حذف البيانات.
- 🔌 **يعمل دون إنترنت**: كل المكتبات محلية.

---

<a id="ar-sales"></a>

## 🧾 المبيعات ونقطة البيع (POS)

**الملفات:** `pos/index.php` · `pos/api.php` · `assets/js/pos.js` · `sales/` · `includes/accounting.php`

- شاشة **نقطة البيع** (`pos/`) تتطلب صلاحية `create_sale`، وتتصل بنقطة نهاية JSON في `pos/api.php`.
- كل عملية بيع تتم داخل **Transaction** في `create_sale()`، وتُنفّذ القواعد التالية فعليًا:
  - رفض الفاتورة الفارغة أو الكمية/السعر غير الصالح أو تكرار المنتج داخل الفاتورة.
  - رفض البيع عند **عدم كفاية المخزون**.
  - البيع **بأقل من الحد الأدنى للسعر** (`min_price`) مرفوض إلا لمن يملك صلاحية `override_min_price`، ويُحفظ سبب التجاوز.
  - البيع **الآجل/الجزئي** يتطلب صلاحية `credit_sale`.
  - **رمز عميل فريد** (`client_token`) لكل فاتورة: إعادة إرسال نفس الطلب تُرجع الفاتورة نفسها بدل إنشاء نسخة مكررة.
  - ترقيم الفواتير تلقائيًا ببادئة قابلة للتعديل من الإعدادات (الافتراضي `INV`).
- **قائمة الفواتير** (`sales/`) وصفحة الفاتورة (`sales/invoice.php`) مع طباعة، و**PDF** من `sales/pdf.php` (A4 أو 80mm).
- **إلغاء الفاتورة** (`sales/cancel.php`) يتطلب صلاحية `cancel_sale` وسببًا: يُرجع المخزون، ويعكس الدفعات المسجّلة (تُسجَّل كحركات «عكس» وتُردّ للعميل)، ويصحّح رصيد العميل.
- الكاشير يرى **فواتيره فقط** ما لم تُمنح له صلاحية `view_all_sales` (حماية من الوصول لفواتير غيره).

---

<a id="ar-inventory"></a>

## 📦 إدارة المنتجات والمخزون

**الملفات:** `products/` · `inventory/` · `categories/` · `units/`

- **المنتج:** الاسم، رمز SKU فريد، التصنيف، الوحدة، سعر التكلفة، سعر البيع الأساسي، **أقل سعر بيع**، الكمية الحالية (3 خانات عشرية)، **حد التنبيه للمخزون**، والحالة (نشط/معطّل).
- **التصنيفات والوحدات** تُدار من صفحتين مستقلتين (صلاحية `manage_catalog`). البيانات الأولية تتضمن 5 تصنيفات و 6 وحدات (قطعة، كرتونة، متر، كيلو، طن، جوال).
- **المخزون** (`inventory/`): عرض الكميات، **سجل حركات** لكل منتج، و**تسوية** يدوية للكمية (صلاحية `manage_inventory`).
- جدول `inventory_movements` يسجّل نوع الحركة: `sale` · `purchase` · `adjustment` · `return` · `purchase_cancel` مع الكمية **قبل وبعد** الحركة.
- المنتجات التي وصلت لحد التنبيه تظهر في لوحة التحكم وفي تقرير «المخزون المنخفض».

---

<a id="ar-purchases"></a>

## 🚚 المشتريات والموردون

**الملفات:** `purchases/` · `suppliers/` · `purchases/pdf.php` · `suppliers/statement.php`

- **فاتورة شراء** (صلاحية `create_purchase`) تزيد مخزون المنتجات وتُنشئ سجل المشتريات.
- **سجل دفعات الموردين** (`supplier_payments`): كل دفعة سجل حقيقي بطريقة دفع وتاريخ ومرجع ومستخدم. القيم `purchases.paid` و`pay_status` و`suppliers.balance` **تُحسب دائمًا من هذا السجل**.
- **دفعة جديدة** (صلاحية `pay_supplier`) من صفحة فاتورة الشراء أو من تفاصيل المورد، وتُرفض في الحالات التالية: مبلغ صفر أو سالب، أكبر من المتبقي، فاتورة مسدّدة أو ملغاة، طريقة دفع غير صالحة، تاريخ مستقبلي أو قبل تاريخ الفاتورة.
- **إلغاء الشراء** (صلاحية `cancel_purchase`) يعيد المخزون (ويُرفض إذا كانت الكمية قد استُهلكت) ويعكس الدفعات كسجلات «استرداد» دون حذف أي تاريخ.
- **كشف حساب المورد** (`suppliers/statement.php`) مع فلتر تواريخ وطباعة وتصدير PDF/CSV.
- **PDF لفاتورة الشراء** من `purchases/pdf.php?id=`.

---

<a id="ar-customers"></a>

## 👥 إدارة العملاء

**الملفات:** `customers/index.php` · `customers/payment.php` · `customers/statement.php`

- بيانات العميل: الاسم، الهاتف، العنوان، الملاحظات، الرصيد، والحالة (نشط/معطّل). العميل المعطّل لا يمكن البيع له.
- **كشف حساب العميل** (`customers/statement.php`) مع طباعة وتصدير.
- صلاحيات الإدارة: `manage_customers`، وحذف السجلات يتطلب `delete_records`.

---

<a id="ar-payments"></a>

## 💳 المدفوعات والديون والمبالغ المستحقة

**الملفات:** `customers/payment.php` · `payment_methods/` · `includes/accounting.php`

- **طرق الدفع** قابلة للإدارة (صلاحية `manage_payment_methods`)، والبيانات الأولية: «كاش» و«بنك».
- **دفعة عميل** (صلاحية `receive_customer_payment`) تُسجَّل في جدول `payments`، وتُوزَّع تلقائيًا على **أقدم الفواتير المفتوحة أولًا** عبر جدول `payment_allocations`.
- حالة الفاتورة المالية: `paid` (مدفوعة) · `partial` (جزئية) · `credit` (آجلة).
- **رصيد العميل** = إجمالي فواتيره غير الملغاة − مجموع دفعاته. وتتم كل العمليات المحاسبية داخل Transaction.
- **«الفحص والإصلاح»** في صفحة الإعدادات يعيد احتساب أرصدة العملاء والموردين وحالات الفواتير ويسجّل أي تغيير في سجل العمليات.

---

<a id="ar-expenses"></a>

## 💸 المصروفات

**الملفات:** `expenses/` · `expense_categories/`

- تسجيل المصروفات بتصنيفاتها (كهرباء، نقل، صيانة، رواتب، مصروفات تشغيل، أخرى — بيانات أولية قابلة للتعديل).
- **إلغاء مصروف** (`expenses/cancel.php`) بصلاحية `cancel_expense` دون حذف السجل.
- تظهر المصروفات في تقرير مستقل وفي لوحة التحكم.

---

<a id="ar-reports"></a>

## 📊 التقارير والإحصائيات

**الملفات:** `reports/index.php` · `includes/reports.php` · `includes/report_page.php` · `includes/dashboard_data.php` · `index.php`

**لوحة التحكم** (`index.php`): مؤشرات الأداء (KPIs) لفترة قابلة للتحديد، «لمحة المبيعات» برسم بياني، رسم دائري لتوزيع المبيعات حسب طريقة الدفع والآجل، المخزون المنخفض، أعلى العملاء مديونية، الأكثر مبيعًا، آخر الفواتير، وآخر العمليات. التحليلات المتقدمة تتطلب صلاحية `view_analytics`.

**التقارير الخمسة عشر** (صفحة `reports/`):

| التقرير | التقرير | التقرير |
|---|---|---|
| المبيعات اليومية | كل الفواتير | المبيعات الشهرية |
| مبيعات الكاشير | طرق الدفع | مبيعات المنتجات |
| مبيعات منتج | تقرير المخزون | المخزون المنخفض |
| المشتريات | المصروفات | أرصدة الموردين |
| دفعات الموردين | أرصدة العملاء | دفعات العملاء |

- لكل تقرير فلاتر مناسبة (التاريخ، المستخدم، العميل، المورد، التصنيف، طريقة الدفع، الحالة…).
- من لا يملك `view_all_sales` يرى **بياناته فقط** في تقارير المبيعات.
- الصلاحيات: `view_reports` و`view_sales_reports`.

---

<a id="ar-roles"></a>

## 👤 المستخدمون والأدوار والصلاحيات

**الملفات:** `users/` · `roles/` · `includes/bootstrap.php`

| الدور | الاسم الداخلي | الوصف |
|---|---|---|
| المدير | `admin` | يملك **كل** الصلاحيات الـ 31 |
| الكاشير | `cashier` | صلاحيات محدودة افتراضيًا: عرض المنتجات، عرض المبيعات، إنشاء بيع، البيع الآجل، إدارة العملاء، تسجيل دفعات العملاء، تقارير المبيعات |

- يمكن تعديل صلاحيات كل دور من صفحة **الأدوار والصلاحيات** (`roles/`) بصلاحية `manage_users` أو `manage_roles`.
- عند الترقية من إصدار قديم يُنقل مستخدمو أي دور آخر (مثل أمين المخزن/المحاسب) تلقائيًا إلى «الكاشير» دون حذف المستخدمين، ويُسجَّل ذلك في سجل العمليات — **راجع صلاحياتهم بعد الترقية**.
- المستخدم له: اسم مستخدم فريد، الاسم الكامل، كلمة مرور مشفّرة بـ `password_hash`، الدور، الحالة، وآخر دخول.
- الصلاحيات **تُقرأ من القاعدة في كل طلب**، فأي تعديل يسري فورًا.

**قائمة الصلاحيات (31):** `view_products` · `create_product` · `edit_product` · `delete_product` · `view_sales` · `create_sale` · `cancel_sale` · `override_min_price` · `credit_sale` · `view_inventory` · `manage_inventory` · `view_reports` · `manage_users` · `manage_settings` · `manage_catalog` · `manage_suppliers` · `manage_customers` · `receive_customer_payment` · `view_purchases` · `create_purchase` · `manage_expenses` · `cancel_expense` · `view_audit` · `manage_payment_methods` · `view_analytics` · `view_all_sales` · `view_sales_reports` · `cancel_purchase` · `delete_records` · `manage_roles` · `pay_supplier`

---

<a id="ar-settings"></a>

## ⚙️ إعدادات النظام

**الملف:** `settings/index.php` (صلاحية `manage_settings`)

- بيانات المنشأة: الاسم، الهاتف، العنوان، البريد، الرقم الضريبي.
- **العملة** (الافتراضي `ج.س`) و**بادئة الفاتورة** و**تذييل الفاتورة**.
- **الشعار:** رفع صورة (حتى 1 ميجابايت) تظهر في الصفحات والفواتير وملفات PDF.
- أداة **«الفحص والإصلاح»** لإعادة احتساب الأرصدة.
- القيم تُخزَّن في جدول `settings` (مفتاح/قيمة) وتنتشر في كل الصفحات.

---

<a id="ar-audit"></a>

## 📝 سجل العمليات والتدقيق (Audit Log)

**الملفات:** `audit/index.php` · `includes/audit_format.php` · جدول `audit_logs`

- يسجّل: المستخدم، نوع العملية، الكيان ومعرّفه، التفاصيل، عنوان IP، والوقت.
- يشمل عمليات البيع والإلغاء والدفعات والتصدير و**محاولات الدخول الفاشلة**.
- صفحة سجل العمليات (صلاحية `view_audit`) مع فلاتر وتصدير **PDF**.

---

<a id="ar-export"></a>

## 🖨️ الطباعة والتصدير

| المخرج | الصيغة | الملف |
|---|---|---|
| فاتورة بيع | PDF بمقاس A4 أو 80mm | `sales/pdf.php` |
| فاتورة شراء | PDF (A4) | `purchases/pdf.php` |
| كشف حساب عميل / مورد | طباعة + PDF + CSV | `customers/statement.php` · `suppliers/statement.php` |
| التقارير (15) | PDF + CSV | `includes/report_page.php` |
| سجل العمليات | PDF | `audit/index.php` |

PDF حقيقي مولَّد بمكتبة **TCPDF 6.7.5** (داخل `lib/tcpdf/`) بخط **DejaVu Sans** واتجاه RTL، ولا يعتمد على طباعة المتصفح.

---

<a id="ar-tech"></a>

## 🛠️ التقنيات المستخدمة

| الطبقة | التقنية |
|---|---|
| الخادم | PHP 8.1+ (PDO) |
| قاعدة البيانات | MySQL / MariaDB (InnoDB، `utf8mb4_unicode_ci`) |
| الواجهة | HTML5 · CSS3 · JavaScript |
| التصميم | Bootstrap 5.3.3 (RTL) · Bootstrap Icons 1.11.3 · خط Cairo (محلي) |
| الرسوم البيانية | Chart.js 4.4.4 |
| PDF | TCPDF 6.7.5 |
| بيئة التطوير | WAMP (Apache + MySQL) على Windows |
| الاختبارات | Python 3 (`requests`، Playwright) — انظر [قسم الاختبارات](#ar-tests) |

---

<a id="ar-tree"></a>

## 📁 هيكل المشروع

```text
joud/
├── index.php                  # لوحة التحكم
├── login.php / logout.php     # الدخول والخروج
├── install.php                # إنشاء أول مدير (مرة واحدة ثم يُحذف)
├── .htaccess                  # منع عرض المجلدات والملفات المخفية
│
├── config/
│   └── config.php             # الاتصال بالقاعدة، BASE، مدة الخمول، المنطقة الزمنية
│
├── includes/                  # قلب النظام (محجوب عن الوصول المباشر)
│   ├── bootstrap.php          # الجلسة، CSRF، PDO، الصلاحيات، التدقيق
│   ├── accounting.php         # منطق البيع والإلغاء والدفعات والأرصدة
│   ├── migrate.php            # ترقية المخطط (v2، v3)
│   ├── helpers.php            # دوال مساعدة، رفع الصور، CSV
│   ├── layout.php             # القالب والقائمة الجانبية
│   ├── crud.php               # CRUD عام للصفحات البسيطة
│   ├── reports.php            # تعريف التقارير
│   ├── report_page.php        # عرض التقارير وتصديرها
│   ├── dashboard_data.php     # بيانات لوحة التحكم
│   ├── invoice.php            # تحميل الفاتورة مع فحص الملكية
│   ├── pdf.php                # توليد PDF عبر TCPDF
│   └── audit_format.php       # تنسيق سجل العمليات
│
├── pos/                       # نقطة البيع (index.php + api.php)
├── sales/                     # الفواتير، الإلغاء، PDF
├── customers/                 # العملاء، دفعات العميل، كشف الحساب
├── products/                  # المنتجات
├── inventory/                 # المخزون وحركاته وتسوياته
├── purchases/                 # فواتير الشراء + PDF
├── suppliers/                 # الموردون، التفاصيل، كشف الحساب
├── expenses/                  # المصروفات + الإلغاء
├── expense_categories/        # تصنيفات المصروفات
├── categories/                # تصنيفات المنتجات
├── units/                     # الوحدات
├── payment_methods/           # طرق الدفع
├── reports/                   # صفحة التقارير
├── users/                     # المستخدمون
├── roles/                     # الأدوار والصلاحيات
├── audit/                     # سجل العمليات
├── settings/                  # الإعدادات والفحص والإصلاح
│
├── database/                  # (محجوب عن الوصول المباشر)
│   ├── joud.sql               # مخطط التثبيت الجديد + البيانات الأولية
│   └── migrate.php            # تشغيل الترقية من سطر الأوامر (CLI فقط)
│
├── assets/
│   ├── css/joud.css           # أنماط المشروع
│   ├── js/                    # joud.js · pos.js · dashboard.js
│   ├── fonts/                 # خط Cairo (woff2)
│   └── vendor/                # bootstrap · icons · chart (نسخ محلية)
│
├── lib/tcpdf/                 # مكتبة TCPDF (طرف ثالث، محجوبة عن الوصول المباشر)
├── uploads/                   # الشعار المرفوع (تنفيذ PHP معطّل فيه)
└── tests/                     # سكربتات اختبار Python — لقاعدة اختبار فقط
```

---

<a id="ar-db"></a>

## 🗄️ قاعدة البيانات والجداول الرئيسية

الاسم الافتراضي: **`joud_building`** — الترميز `utf8mb4` / `utf8mb4_unicode_ci` — المحرك InnoDB. المخطط الحالي فيه **22 جدولًا**:

| الجدول | الغرض |
|---|---|
| `users` | المستخدمون (اسم المستخدم، كلمة المرور المشفّرة، الدور، الحالة، آخر دخول) |
| `roles` | الأدوار (`admin`، `cashier`) |
| `permissions` | قائمة الصلاحيات الـ 31 |
| `role_permissions` | ربط الدور بالصلاحيات |
| `products` | المنتجات والأسعار والمخزون |
| `categories` | تصنيفات المنتجات |
| `units` | وحدات القياس |
| `inventory_movements` | حركات المخزون (قبل/بعد) |
| `customers` | العملاء وأرصدتهم |
| `sales` | رؤوس فواتير البيع |
| `sale_items` | بنود فواتير البيع |
| `payments` | دفعات العملاء والبيع، بنوع `sale` / `customer` / `reversal` |
| `payment_allocations` | توزيع الدفعات على الفواتير |
| `payment_methods` | طرق الدفع |
| `suppliers` | الموردون وأرصدتهم |
| `purchases` | رؤوس فواتير الشراء |
| `purchase_items` | بنود فواتير الشراء |
| `supplier_payments` | سجل دفعات الموردين (دفع/استرداد) |
| `expenses` | المصروفات |
| `expense_categories` | تصنيفات المصروفات |
| `settings` | إعدادات النظام (مفتاح/قيمة) و`schema_version` |
| `audit_logs` | سجل العمليات |

**العلاقات المؤكدة (مفاتيح أجنبية):**

```text
users.role_id               → roles.id
role_permissions            → roles.id , permissions.id        (ON DELETE CASCADE)
products.category_id        → categories.id
products.unit_id            → units.id
sales.user_id               → users.id        sales.customer_id → customers.id
sale_items.sale_id          → sales.id        sale_items.product_id → products.id
payments.sale_id            → sales.id        payments.customer_id  → customers.id
payments.payment_method_id  → payment_methods.id
payment_allocations         → payments.id , sales.id
purchases.supplier_id       → suppliers.id    purchase_items.purchase_id → purchases.id
supplier_payments           → suppliers.id , purchases.id , payment_methods.id
inventory_movements         → products.id , users.id
expenses.category_id        → expense_categories.id
```

**البيانات الأولية في `joud.sql`:** دورا المدير والكاشير (admin، cashier) · 31 صلاحية وربطها بالدورين · 5 تصنيفات · 6 وحدات · طريقتا دفع · 6 تصنيفات مصروفات · الإعدادات الافتراضية. **لا يحتوي الملف على أي مستخدم** — يُنشأ أول مدير من `install.php`.

---

<a id="ar-sql"></a>

## 📦 ملفات SQL والترحيلات

| الملف | الاستخدام |
|---|---|
| `database/joud.sql` | **تثبيت جديد فقط.** يُنشئ القاعدة `joud_building` إن لم تكن موجودة ويبني الجداول والبيانات الأولية. **لا يُستورد فوق قاعدة موجودة.** |
| `includes/migrate.php` | ترقية المخطط تلقائيًا: `migrate_v2` (المحاسبة وتوزيع الدفعات، الأدوار) ثم `migrate_v3` (سجل دفعات الموردين). يستخدم قفلًا (`GET_LOCK`) لمنع التشغيل المتزامن، ويتخطى ما هو مطبّق. |
| `database/migrate.php` | تشغيل نفس الترقية يدويًا من سطر الأوامر: `php database\migrate.php` (يرفض الاستدعاء من المتصفح). |
| `tests/legacy_seed.sql` | بيانات «نظام قديم» **لاختبار الترقية فقط** — لا تستورده في قاعدة حقيقية. |

> ⚠️ **تنبيه:** خذ **نسخة احتياطية** من قاعدة البيانات (phpMyAdmin → Export) قبل أي ترقية أو تعديل في القاعدة.

---

<a id="ar-install"></a>

## 🚀 التثبيت والتشغيل محليًا باستخدام WAMP

### المتطلبات

- **WAMP** (Apache + MySQL/MariaDB + PHP **8.1 أو أحدث**).
- إضافات PHP مفعّلة: `pdo_mysql` · `mbstring` · `fileinfo` · `gd`.
- تمكين `.htaccess` في Apache (`AllowOverride All`) لتعمل حمايات المجلدات.

### 🆕 تثبيت جديد

1. انسخ المجلد إلى مجلد WAMP، مثلًا: `C:\wamp64\www\joud` (يتوافق مع `BASE` الافتراضي `/joud`).
2. شغّل **Apache و MySQL** وتأكد أن أيقونة WAMP خضراء.
3. افتح **phpMyAdmin** واستورد `database/joud.sql` (الملف يُنشئ قاعدة `joud_building` ويختارها).
4. راجع `config/config.php` (القيم الافتراضية لـ WAMP: المضيف `localhost`، المستخدم `root`، بدون كلمة مرور).
5. افتح `http://localhost/joud/install.php` وأنشئ **حساب المدير الأول** (كلمة المرور 10 أحرف على الأقل).
6. **احذف `install.php`** بعد الانتهاء (يُقفل التثبيت تلقائيًا بعد إنشاء أول مستخدم).
7. افتح `http://localhost/joud/` وسجّل الدخول.

### 🔄 ترقية نسخة موجودة

1. **خذ نسخة احتياطية من القاعدة أولًا.**
2. استبدل ملفات المشروع **مع الإبقاء على** `config/config.php` (إن عدّلته) ومجلد `uploads/` (فيه الشعار).
3. **لا تستورد `joud.sql`.** افتح `http://localhost/joud/` وسجّل الدخول: تُنفَّذ الترقية تلقائيًا إن كان `schema_version` أقل من 3 (آمنة ولا تحذف بيانات)، أو شغّل `php database\migrate.php` من مجلد المشروع.
4. راجع صلاحيات المستخدمين من «الأدوار والصلاحيات» إن كانت لديك أدوار قديمة.

### ⚙️ الإعدادات (`config/config.php`)

```php
define('DB_HOST', getenv('JOUD_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('JOUD_DB_NAME') ?: 'joud_building');
define('DB_USER', getenv('JOUD_DB_USER') ?: 'root');
define('DB_PASS', /* من JOUD_DB_PASS أو فارغة افتراضيًا */);
define('BASE', /* من JOUD_BASE أو '/joud' افتراضيًا */);   // مسار المشروع على الخادم
define('SESSION_IDLE_MINUTES', 120);                        // مدة الخمول قبل إنهاء الجلسة
define('APP_DEBUG', false);                                 // true أثناء التطوير فقط
date_default_timezone_set('Africa/Khartoum');
```

- إن وضعت المشروع في مجلد باسم مختلف، غيّر `BASE` ليطابقه.
- لا تنشر كلمات مرور القاعدة. على أي خادم غير محلي اجعل لمستخدم القاعدة كلمة مرور قوية.

---

<a id="ar-security"></a>

## 🔐 الأمان والخصوصية

| الحماية | التفاصيل |
|---|---|
| CSRF | رمز لكل جلسة يُفحص بـ `hash_equals` في كل طلب POST |
| حقن SQL | استعلامات PDO محضّرة (Prepared Statements) |
| XSS | تهريب المخرجات عبر `e()` |
| كلمات المرور | `password_hash` / `password_verify`، وحد أدنى 10 أحرف عند إنشاء المدير الأول |
| الجلسات | `session_regenerate_id` عند الدخول، `HttpOnly`، `SameSite=Lax`، و`Secure` عند HTTPS، ووضع `strict` |
| الخمول | إنهاء الجلسة بعد `SESSION_IDLE_MINUTES` (120 دقيقة افتراضيًا) |
| محاولات الدخول | قفل بعد **8 محاولات فاشلة خلال 15 دقيقة لكل عنوان IP**، مبني على `audit_logs` |
| الصلاحيات | تُقرأ من القاعدة في كل طلب |
| IDOR | الكاشير لا يرى فواتير غيره إلا بصلاحية `view_all_sales` |
| رفع الملفات | الشعار فقط: فحص النوع (`finfo`) و`getimagesize`، حد 1 ميجابايت، اسم عشوائي، وتعطيل تنفيذ PHP داخل `uploads/` |
| حجب الوصول | `.htaccess` يمنع الوصول المباشر إلى `config` و`includes` و`database` و`lib` و`tests` |
| التدقيق | العمليات الحساسة ومحاولات الدخول الفاشلة تُسجَّل في `audit_logs` |

**ملاحظات أمنية للتشغيل:**

- احذف `install.php` بعد التثبيت.
- لا ترفع `config/config.php` بكلمات مرور حقيقية إلى GitHub.
- لا تشغّل سكربتات `tests/` على قاعدة حقيقية (تنشئ بيانات تجريبية وتحوي كلمات مرور اختبار).
- لا توجد في المشروع مصادقة ثنائية، ولا إعدادات HTTPS جاهزة؛ فعّل HTTPS عند النشر خارج الشبكة المحلية.

---

<a id="ar-tests"></a>

## 🧪 الاختبارات وحالة المشروع

### ✅ ما تم التحقق منه فعليًا

- فحص الصياغة `php -l` على **جميع ملفات PHP** الخاصة بالمشروع (باستثناء `lib/`): **0 أخطاء** (PHP 8.3.6 CLI).
- فحص `node --check` لملفات `assets/js/joud.js` و`pos.js` و`dashboard.js`: **سليمة**.

### 📂 سكربتات الاختبار الموجودة في `tests/` (لم تُشغَّل ضمن هذا التوثيق)

| الملف | الغرض |
|---|---|
| `e2e_http.py` | اختبار شامل عبر HTTP (دخول، بيع، دفعات، إلغاء، صلاحيات) |
| `e2e_edge.py` | حالات حدّية وأمان (XSS، حقن SQL، رفع ملفات) |
| `e2e_suppliers.py` | الموردون والمشتريات والدفعات |
| `browser_responsive.py` | تجاوب الواجهة وعدم وجود تمرير أفقي (Playwright/Chromium) |
| `browser_suppliers.py` | دفعة مورد من الواجهة وتجاوب الصفحات |
| `legacy_seed.sql` | بيانات لاختبار الترقية من نظام قديم |

**المتطلبات:** Python 3 مع `requests` و`playwright`، وأداة `mysql` في سطر الأوامر، وخادم يعمل، و**قاعدة اختبار فارغة**. ⚠️ هذه السكربتات **تنشئ بيانات وتعدّلها** — شغّلها على قاعدة اختبار فقط.

### 🔎 غير مؤكد / موصى به

- لم تُشغَّل سكربتات الاختبار في هذا التوثيق، فلا تُنسب لها نتائج هنا.
- لم يُتحقق من التشغيل على متصفح حقيقي أو على Windows/WAMP أو على خادم إنتاج ضمن هذا التوثيق؛ يُوصى بتجربة الفواتير والنماذج الرئيسية على بيئة اختبار قبل الاستخدام الفعلي.
- لا توجد إعدادات تكامل مستمر (CI) في المستودع.

---

<a id="ar-limits"></a>

## 🌐 النشر والاستضافة والقيود المعروفة

- المشروع مصمَّم للتشغيل **المحلي على WAMP** (Apache + PHP + MySQL). عند النشر على استضافة: فعّل HTTPS، وغيّر `BASE` و`DB_*`، وأنشئ مستخدم قاعدة بصلاحيات محدودة بكلمة مرور قوية.
- ملف `uploads/.htaccess` يستخدم `php_flag`، وهو يعمل مع `mod_php`؛ على بيئات PHP-FPM/CGI قد يسبب خطأ 500 ويحتاج تعديلًا يناسب الخادم (**يلزم التحقق على بيئة النشر**).
- حمايات المجلدات تعتمد على `.htaccess` (Apache). على Nginx يجب ضبط ما يعادلها يدويًا.
- قفل محاولات الدخول يعتمد على عنوان IP وسجل التدقيق.
- الواجهة عربية فقط، والعملة المعتمدة واحدة تُضبط من الإعدادات.
- مجلد `desktop/` (نسخة سطح المكتب) **غير موجود** في هذه النسخة من المشروع، ولذلك لا يغطيها هذا التوثيق.

---

<a id="ar-dev"></a>

## 👨‍💻 المطوّر

<div align="center">

**Software Developer · Web & Flutter Developer · AI Developer**

*Building Modern Web, Mobile & AI Applications*

🔗 [github.com/awabwdbashry-sketch](https://github.com/awabwdbashry-sketch)

</div>

## ❤️ كلمة ختامية

بُني **JOUD** ليخدم تجارة مواد البناء بواقعية: حسابات تُحسب من سجلات حقيقية لا من أرقام مخزّنة يدويًا، ومخزون يمكن تتبّع كل حركة فيه، وصلاحيات تحمي البيانات، وسجل تدقيق يجيب عن «من فعل ماذا ومتى». نرحّب بالملاحظات والمقترحات عبر قسم **Issues** في المستودع.

<div align="right"><a href="#arabic">↑ العودة إلى الأعلى</a></div>

---

<a id="english"></a>

# 🇬🇧 English

> 🇸🇩 *الوثائق العربية في [الأعلى](#arabic).*

## 📑 Table of Contents

1. [About](#en-about)
2. [Key Features](#en-features)
3. [Sales & Point of Sale](#en-sales)
4. [Products & Inventory](#en-inventory)
5. [Purchases & Suppliers](#en-purchases)
6. [Customers](#en-customers)
7. [Payments, Debts & Balances](#en-payments)
8. [Expenses](#en-expenses)
9. [Reports & Dashboard](#en-reports)
10. [Users, Roles & Permissions](#en-roles)
11. [System Settings](#en-settings)
12. [Audit Log](#en-audit)
13. [Printing & Export](#en-export)
14. [Tech Stack](#en-tech)
15. [Project Structure](#en-tree)
16. [Database](#en-db)
17. [SQL Files & Migrations](#en-sql)
18. [Installation on WAMP](#en-install)
19. [Security & Privacy](#en-security)
20. [Testing & Project Status](#en-tests)
21. [Deployment & Known Limitations](#en-limits)
22. [Developer](#en-dev)

---

<a id="en-about"></a>

## 📌 About

**JOUD (جود لمواد البناء)** is a web application built with **PHP and MySQL/MariaDB** for managing a **building-materials** business. The interface is fully Arabic with an **RTL** layout, and it runs **locally on WAMP without an internet connection**: Bootstrap RTL, icons, Chart.js, the Cairo font, and TCPDF are all bundled inside the project and nothing is loaded from external CDNs.

It covers the day-to-day workflow: selling from a point-of-sale screen, tracking stock and every stock movement, buying from suppliers and recording supplier payments, customer debts and repayments, expenses, exportable reports, and an audit trail of sensitive operations.

| Item | Value |
|---|---|
| Project | JOUD — جود لمواد البناء |
| Language / direction | Arabic (RTL) |
| Server language | PHP 8.1+ (syntax-checked on PHP 8.3) |
| Database | MySQL / MariaDB — default name `joud_building` |
| Local environment | WAMP on Windows |
| Built-in roles | Admin (`admin`) and Cashier (`cashier`) |
| Current schema | `schema_version = 3` |
| Repository | [JOUD-Building-Materials-Management-System](https://github.com/awabwdbashry-sketch/JOUD-Building-Materials-Management-System) |

---

<a id="en-features"></a>

## ✨ Key Features

- 🧾 **Point of Sale (POS)** with cash, credit and partial-payment sales, discounts, and a unique token per invoice that prevents duplicates on resubmission.
- 📦 **Products & stock** with a minimum selling price and a low-stock threshold, a full movement log (before/after), and manual adjustments.
- 🚚 **Purchases & suppliers** with a supplier-payment ledger (at purchase, later, or refund) and balances derived from that ledger.
- 👥 **Customers & debts:** customer balance = total non-cancelled invoices − total payments, with payments allocated to the oldest invoices first.
- 💸 **Expenses** with categories and the ability to cancel an expense.
- 📊 **15 reports** with filters, **PDF and CSV** export, and a dashboard with charts.
- 🔐 **Fine-grained roles & permissions (31 permissions)** read from the database on every request.
- 📝 **Audit log** for sensitive operations and failed login attempts.
- 🖨️ **Real PDF invoices** in **A4** and **80mm** thermal receipt sizes via TCPDF.
- 🔁 **Automatic database upgrade** from older versions without deleting data.
- 🔌 **Works offline:** every library is bundled locally.

---

<a id="en-sales"></a>

## 🧾 Sales & Point of Sale (POS)

**Files:** `pos/index.php` · `pos/api.php` · `assets/js/pos.js` · `sales/` · `includes/accounting.php`

- The **POS screen** (`pos/`) requires the `create_sale` permission and talks to a JSON endpoint in `pos/api.php`.
- Every sale runs inside a **transaction** in `create_sale()` and enforces the following rules:
  - Rejects empty invoices, invalid quantity/price, and duplicate products in one invoice.
  - Rejects sales when **stock is insufficient**.
  - Selling **below the minimum price** (`min_price`) is rejected unless the user has `override_min_price`; the override reason is stored.
  - **Credit/partial** sales require the `credit_sale` permission.
  - A unique **client token** (`client_token`) per invoice: resubmitting the same request returns the existing invoice instead of creating a duplicate.
  - Invoice numbers are generated automatically with a configurable prefix (default `INV`).
- **Invoice list** (`sales/`) and invoice page (`sales/invoice.php`) with printing, and **PDF** from `sales/pdf.php` (A4 or 80mm).
- **Cancelling an invoice** (`sales/cancel.php`) needs the `cancel_sale` permission and a reason: it restores stock, reverses recorded payments (stored as "reversal" entries and refunded to the customer), and corrects the customer balance.
- A cashier sees **only their own invoices** unless granted `view_all_sales`.

---

<a id="en-inventory"></a>

## 📦 Products & Inventory

**Files:** `products/` · `inventory/` · `categories/` · `units/`

- **Product:** name, unique SKU, category, unit, cost price, base selling price, **minimum selling price**, current stock (3 decimals), **low-stock threshold**, and status (active/inactive).
- **Categories and units** have their own pages (`manage_catalog` permission). Seed data includes 5 categories and 6 units (piece, carton, meter, kilo, ton, sack).
- **Inventory** (`inventory/`): stock overview, a **movement history** per product, and manual **stock adjustment** (`manage_inventory`).
- The `inventory_movements` table records the movement type — `sale` · `purchase` · `adjustment` · `return` · `purchase_cancel` — with quantity **before and after**.
- Products at or below their threshold appear on the dashboard and in the "Low stock" report.

---

<a id="en-purchases"></a>

## 🚚 Purchases & Suppliers

**Files:** `purchases/` · `suppliers/` · `purchases/pdf.php` · `suppliers/statement.php`

- A **purchase invoice** (`create_purchase`) increases product stock and creates the purchase record.
- **Supplier payment ledger** (`supplier_payments`): every payment is a real record with a payment method, date, reference, and user. `purchases.paid`, `pay_status`, and `suppliers.balance` are **always recomputed from this ledger**.
- **New payment** (`pay_supplier`) from the purchase page or the supplier details page. Rejected when: amount is zero or negative, exceeds the remaining balance, the invoice is settled or cancelled, the payment method is invalid, or the date is in the future or before the invoice date.
- **Cancelling a purchase** (`cancel_purchase`) restores stock (rejected if the quantity was already consumed) and reverses payments as "refund" records without deleting any history.
- **Supplier statement** (`suppliers/statement.php`) with date filters, printing, and PDF/CSV export.
- **Purchase invoice PDF** from `purchases/pdf.php?id=`.

---

<a id="en-customers"></a>

## 👥 Customers

**Files:** `customers/index.php` · `customers/payment.php` · `customers/statement.php`

- Customer data: name, phone, address, notes, balance, and status (active/inactive). Inactive customers cannot be sold to.
- **Customer statement** (`customers/statement.php`) with printing and export.
- Management requires `manage_customers`; deleting records requires `delete_records`.

---

<a id="en-payments"></a>

## 💳 Payments, Debts & Balances

**Files:** `customers/payment.php` · `payment_methods/` · `includes/accounting.php`

- **Payment methods** are manageable (`manage_payment_methods`); seed data: "Cash" and "Bank".
- A **customer payment** (`receive_customer_payment`) is stored in `payments` and automatically **allocated to the oldest open invoices first** through `payment_allocations`.
- Invoice payment status: `paid` · `partial` · `credit`.
- **Customer balance** = total non-cancelled invoices − total payments. All accounting operations run inside a transaction.
- The **"Check & repair"** tool in Settings recalculates customer and supplier balances and invoice statuses, and logs any change in the audit log.

---

<a id="en-expenses"></a>

## 💸 Expenses

**Files:** `expenses/` · `expense_categories/`

- Record expenses under categories (electricity, transport, maintenance, salaries, operating costs, other — editable seed data).
- **Cancel an expense** (`expenses/cancel.php`) with the `cancel_expense` permission, without deleting the record.
- Expenses appear in a dedicated report and on the dashboard.

---

<a id="en-reports"></a>

## 📊 Reports & Dashboard

**Files:** `reports/index.php` · `includes/reports.php` · `includes/report_page.php` · `includes/dashboard_data.php` · `index.php`

**Dashboard** (`index.php`): KPIs for a selectable period, a sales-overview chart, a pie chart splitting sales by payment method and credit, low stock, top indebted customers, best sellers, latest invoices, and latest operations. Advanced analytics require `view_analytics`.

**The 15 reports** (`reports/` page):

| Report | Report | Report |
|---|---|---|
| Daily sales | All invoices | Monthly sales |
| Cashier sales | Payment methods | Product sales |
| Single-product sales | Inventory report | Low stock |
| Purchases | Expenses | Supplier balances |
| Supplier payments | Customer balances | Customer payments |

- Each report has relevant filters (date, user, customer, supplier, category, payment method, status…).
- Users without `view_all_sales` see **only their own data** in sales reports.
- Permissions: `view_reports` and `view_sales_reports`.

---

<a id="en-roles"></a>

## 👤 Users, Roles & Permissions

**Files:** `users/` · `roles/` · `includes/bootstrap.php`

| Role | Internal name | Description |
|---|---|---|
| Admin | `admin` | Holds **all** 31 permissions |
| Cashier | `cashier` | Limited by default: view products, view sales, create sale, credit sale, manage customers, receive customer payments, sales reports |

- Each role's permissions can be edited from the **Roles & Permissions** page (`roles/`) with `manage_users` or `manage_roles`.
- When upgrading from an older version, users of any other role (e.g. storekeeper/accountant) are moved automatically to "Cashier" without deleting the users, and this is recorded in the audit log — **review their permissions after upgrading**.
- A user has: unique username, full name, a `password_hash`-encrypted password, role, status, and last login.
- Permissions are **read from the database on every request**, so changes apply immediately.

**Permission list (31):** `view_products` · `create_product` · `edit_product` · `delete_product` · `view_sales` · `create_sale` · `cancel_sale` · `override_min_price` · `credit_sale` · `view_inventory` · `manage_inventory` · `view_reports` · `manage_users` · `manage_settings` · `manage_catalog` · `manage_suppliers` · `manage_customers` · `receive_customer_payment` · `view_purchases` · `create_purchase` · `manage_expenses` · `cancel_expense` · `view_audit` · `manage_payment_methods` · `view_analytics` · `view_all_sales` · `view_sales_reports` · `cancel_purchase` · `delete_records` · `manage_roles` · `pay_supplier`

---

<a id="en-settings"></a>

## ⚙️ System Settings

**File:** `settings/index.php` (`manage_settings`)

- Business details: name, phone, address, email, tax number.
- **Currency** (default `ج.س`), **invoice prefix**, and **invoice footer**.
- **Logo:** image upload (up to 1 MB) shown across pages, invoices, and PDFs.
- The **"Check & repair"** tool for recalculating balances.
- Values are stored in the `settings` key/value table and apply everywhere.

---

<a id="en-audit"></a>

## 📝 Audit Log

**Files:** `audit/index.php` · `includes/audit_format.php` · `audit_logs` table

- Records: user, action, entity and its ID, details, IP address, and timestamp.
- Covers sales, cancellations, payments, exports, and **failed login attempts**.
- The audit page (`view_audit`) offers filters and **PDF** export.

---

<a id="en-export"></a>

## 🖨️ Printing & Export

| Output | Format | File |
|---|---|---|
| Sales invoice | PDF, A4 or 80mm | `sales/pdf.php` |
| Purchase invoice | PDF (A4) | `purchases/pdf.php` |
| Customer / supplier statement | Print + PDF + CSV | `customers/statement.php` · `suppliers/statement.php` |
| Reports (15) | PDF + CSV | `includes/report_page.php` |
| Audit log | PDF | `audit/index.php` |

PDFs are generated server-side with **TCPDF 6.7.5** (inside `lib/tcpdf/`) using the **DejaVu Sans** font and RTL direction — they do not depend on browser printing.

---

<a id="en-tech"></a>

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Server | PHP 8.1+ (PDO) |
| Database | MySQL / MariaDB (InnoDB, `utf8mb4_unicode_ci`) |
| Front end | HTML5 · CSS3 · JavaScript |
| UI | Bootstrap 5.3.3 (RTL) · Bootstrap Icons 1.11.3 · Cairo font (local) |
| Charts | Chart.js 4.4.4 |
| PDF | TCPDF 6.7.5 |
| Dev environment | WAMP (Apache + MySQL) on Windows |
| Tests | Python 3 (`requests`, Playwright) — see [Testing](#en-tests) |

---

<a id="en-tree"></a>

## 📁 Project Structure

```text
joud/
├── index.php                  # Dashboard
├── login.php / logout.php     # Sign in / out
├── install.php                # Create the first admin (once, then delete)
├── .htaccess                  # Disable directory listing and dotfiles
│
├── config/
│   └── config.php             # DB connection, BASE, idle timeout, timezone
│
├── includes/                  # Application core (direct access denied)
│   ├── bootstrap.php          # Session, CSRF, PDO, permissions, audit
│   ├── accounting.php         # Sales, cancellation, payments, balances
│   ├── migrate.php            # Schema upgrades (v2, v3)
│   ├── helpers.php            # Helpers, image upload, CSV
│   ├── layout.php             # Layout and sidebar navigation
│   ├── crud.php               # Generic CRUD for simple pages
│   ├── reports.php            # Report definitions
│   ├── report_page.php        # Report rendering and export
│   ├── dashboard_data.php     # Dashboard data
│   ├── invoice.php            # Invoice loading with ownership check
│   ├── pdf.php                # PDF generation via TCPDF
│   └── audit_format.php       # Audit log formatting
│
├── pos/                       # Point of sale (index.php + api.php)
├── sales/                     # Invoices, cancellation, PDF
├── customers/                 # Customers, customer payments, statement
├── products/                  # Products
├── inventory/                 # Stock, movements, adjustments
├── purchases/                 # Purchase invoices + PDF
├── suppliers/                 # Suppliers, details, statement
├── expenses/                  # Expenses + cancellation
├── expense_categories/        # Expense categories
├── categories/                # Product categories
├── units/                     # Units of measure
├── payment_methods/           # Payment methods
├── reports/                   # Reports page
├── users/                     # Users
├── roles/                     # Roles & permissions
├── audit/                     # Audit log
├── settings/                  # Settings, check & repair
│
├── database/                  # (direct access denied)
│   ├── joud.sql               # Fresh-install schema + seed data
│   └── migrate.php            # Run upgrades from the CLI (CLI only)
│
├── assets/
│   ├── css/joud.css           # Project styles
│   ├── js/                    # joud.js · pos.js · dashboard.js
│   ├── fonts/                 # Cairo font (woff2)
│   └── vendor/                # bootstrap · icons · chart (local copies)
│
├── lib/tcpdf/                 # TCPDF (third party, direct access denied)
├── uploads/                   # Uploaded logo (PHP execution disabled)
└── tests/                     # Python test scripts — test database only
```

---

<a id="en-db"></a>

## 🗄️ Database & Main Tables

Default name: **`joud_building`** — charset `utf8mb4` / `utf8mb4_unicode_ci` — InnoDB engine. The current schema has **22 tables**:

| Table | Purpose |
|---|---|
| `users` | Users (username, hashed password, role, status, last login) |
| `roles` | Roles (`admin`, `cashier`) |
| `permissions` | The 31 permissions |
| `role_permissions` | Role ↔ permission mapping |
| `products` | Products, prices, and stock |
| `categories` | Product categories |
| `units` | Units of measure |
| `inventory_movements` | Stock movements (before/after) |
| `customers` | Customers and balances |
| `sales` | Sales invoice headers |
| `sale_items` | Sales invoice lines |
| `payments` | Sale and customer payments, typed `sale` / `customer` / `reversal` |
| `payment_allocations` | Allocation of payments to invoices |
| `payment_methods` | Payment methods |
| `suppliers` | Suppliers and balances |
| `purchases` | Purchase invoice headers |
| `purchase_items` | Purchase invoice lines |
| `supplier_payments` | Supplier payment ledger (payment/refund) |
| `expenses` | Expenses |
| `expense_categories` | Expense categories |
| `settings` | System settings (key/value) and `schema_version` |
| `audit_logs` | Audit trail |

**Confirmed relationships (foreign keys):**

```text
users.role_id               → roles.id
role_permissions            → roles.id , permissions.id        (ON DELETE CASCADE)
products.category_id        → categories.id
products.unit_id            → units.id
sales.user_id               → users.id        sales.customer_id → customers.id
sale_items.sale_id          → sales.id        sale_items.product_id → products.id
payments.sale_id            → sales.id        payments.customer_id  → customers.id
payments.payment_method_id  → payment_methods.id
payment_allocations         → payments.id , sales.id
purchases.supplier_id       → suppliers.id    purchase_items.purchase_id → purchases.id
supplier_payments           → suppliers.id , purchases.id , payment_methods.id
inventory_movements         → products.id , users.id
expenses.category_id        → expense_categories.id
```

**Seed data in `joud.sql`:** two roles (admin, cashier) · 31 permissions mapped to the roles · 5 categories · 6 units · two payment methods · 6 expense categories · default settings. **The file contains no users** — the first admin is created through `install.php`.

---

<a id="en-sql"></a>

## 📦 SQL Files & Migrations

| File | Usage |
|---|---|
| `database/joud.sql` | **Fresh install only.** Creates the `joud_building` database if missing and builds tables and seed data. **Never import it over an existing database.** |
| `includes/migrate.php` | Automatic schema upgrade: `migrate_v2` (accounting, payment allocations, roles) then `migrate_v3` (supplier payment ledger). Uses a lock (`GET_LOCK`) to prevent concurrent runs and skips what is already applied. |
| `database/migrate.php` | Runs the same upgrade manually from the command line: `php database\migrate.php` (refuses browser access). |
| `tests/legacy_seed.sql` | "Legacy system" data **for upgrade testing only** — do not import into a real database. |

> ⚠️ **Warning:** take a **database backup** (phpMyAdmin → Export) before any upgrade or database change.

---

<a id="en-install"></a>

## 🚀 Local Installation on WAMP

### Requirements

- **WAMP** (Apache + MySQL/MariaDB + PHP **8.1 or newer**).
- PHP extensions enabled: `pdo_mysql` · `mbstring` · `fileinfo` · `gd`.
- `.htaccess` enabled in Apache (`AllowOverride All`) so the folder protections work.

### 🆕 Fresh installation

1. Copy the folder into WAMP's web root, e.g. `C:\wamp64\www\joud` (matches the default `BASE` of `/joud`).
2. Start **Apache and MySQL** and make sure the WAMP icon is green.
3. Open **phpMyAdmin** and import `database/joud.sql` (it creates and selects the `joud_building` database).
4. Review `config/config.php` (WAMP defaults: host `localhost`, user `root`, empty password).
5. Open `http://localhost/joud/install.php` and create the **first admin account** (password of at least 10 characters).
6. **Delete `install.php`** when done (installation locks automatically once the first user exists).
7. Open `http://localhost/joud/` and sign in.

### 🔄 Upgrading an existing installation

1. **Back up the database first.**
2. Replace the project files **while keeping** `config/config.php` (if customized) and the `uploads/` folder (it holds the logo).
3. **Do not import `joud.sql`.** Open `http://localhost/joud/` and sign in: the upgrade runs automatically when `schema_version` is below 3 (safe, no data deleted), or run `php database\migrate.php` from the project folder.
4. Review user permissions under "Roles & Permissions" if you had legacy roles.

### ⚙️ Configuration (`config/config.php`)

```php
define('DB_HOST', getenv('JOUD_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('JOUD_DB_NAME') ?: 'joud_building');
define('DB_USER', getenv('JOUD_DB_USER') ?: 'root');
define('DB_PASS', /* from JOUD_DB_PASS, empty by default */);
define('BASE', /* from JOUD_BASE, '/joud' by default */);   // project path on the server
define('SESSION_IDLE_MINUTES', 120);                         // idle time before the session ends
define('APP_DEBUG', false);                                  // true during development only
date_default_timezone_set('Africa/Khartoum');
```

- If you place the project in a differently named folder, change `BASE` to match.
- Never publish database passwords. On any non-local server, give the database user a strong password.

---

<a id="en-security"></a>

## 🔐 Security & Privacy

| Protection | Details |
|---|---|
| CSRF | Per-session token checked with `hash_equals` on every POST |
| SQL injection | PDO prepared statements |
| XSS | Output escaping via `e()` |
| Passwords | `password_hash` / `password_verify`; minimum 10 characters when creating the first admin |
| Sessions | `session_regenerate_id` on login, `HttpOnly`, `SameSite=Lax`, `Secure` over HTTPS, strict mode |
| Idle timeout | Session ends after `SESSION_IDLE_MINUTES` (120 by default) |
| Login attempts | Lockout after **8 failed attempts within 15 minutes per IP address**, based on `audit_logs` |
| Permissions | Read from the database on every request |
| IDOR | A cashier cannot view others' invoices without `view_all_sales` |
| File uploads | Logo only: type check (`finfo`) and `getimagesize`, 1 MB limit, random file name, PHP execution disabled inside `uploads/` |
| Access blocking | `.htaccess` denies direct access to `config`, `includes`, `database`, `lib`, and `tests` |
| Auditing | Sensitive operations and failed logins are written to `audit_logs` |

**Operational security notes:**

- Delete `install.php` after installation.
- Do not push `config/config.php` with real passwords to GitHub.
- Never run the `tests/` scripts against a real database (they create data and contain test passwords).
- The project has no two-factor authentication and no ready-made HTTPS configuration; enable HTTPS when deploying beyond the local network.

---

<a id="en-tests"></a>

## 🧪 Testing & Project Status

### ✅ Actually verified

- `php -l` syntax check on **all of the project's PHP files** (excluding `lib/`): **0 errors** (PHP 8.3.6 CLI).
- `node --check` on `assets/js/joud.js`, `pos.js`, and `dashboard.js`: **valid**.

### 📂 Test scripts in `tests/` (not executed while preparing this documentation)

| File | Purpose |
|---|---|
| `e2e_http.py` | End-to-end HTTP test (login, sales, payments, cancellation, permissions) |
| `e2e_edge.py` | Edge cases and security (XSS, SQL injection, file upload) |
| `e2e_suppliers.py` | Suppliers, purchases, and payments |
| `browser_responsive.py` | UI responsiveness and no horizontal scroll (Playwright/Chromium) |
| `browser_suppliers.py` | Supplier payment from the UI and page responsiveness |
| `legacy_seed.sql` | Data for testing the upgrade from a legacy system |

**Requirements:** Python 3 with `requests` and `playwright`, the `mysql` command-line client, a running server, and an **empty test database**. ⚠️ These scripts **create and modify data** — run them on a test database only.

### 🔎 Unverified / recommended

- The test scripts were not run for this documentation, so no results are claimed for them.
- Operation in a real browser, on Windows/WAMP, or on a production server was not verified here; try the main invoices and forms in a test environment before real use.
- The repository has no continuous-integration (CI) configuration.

---

<a id="en-limits"></a>

## 🌐 Deployment, Hosting & Known Limitations

- The project is designed for **local operation on WAMP** (Apache + PHP + MySQL). When deploying to hosting: enable HTTPS, change `BASE` and `DB_*`, and create a restricted database user with a strong password.
- `uploads/.htaccess` uses `php_flag`, which works with `mod_php`; on PHP-FPM/CGI setups it may cause a 500 error and need a server-appropriate change (**must be verified on the target environment**).
- Folder protections rely on `.htaccess` (Apache). On Nginx, equivalent rules must be configured manually.
- Login lockout is based on IP address and the audit log.
- The interface is Arabic only, and a single currency is configured from Settings.
- A `desktop/` folder (desktop version) is **not present** in this copy of the project, so it is not covered by this documentation.

---

<a id="en-dev"></a>

## 👨‍💻 Developer

<div align="center">

**Software Developer · Web & Flutter Developer · AI Developer**

*Building Modern Web, Mobile & AI Applications*

🔗 [github.com/awabwdbashry-sketch](https://github.com/awabwdbashry-sketch)

</div>

## ❤️ Closing Note

**JOUD** was built to serve the building-materials trade realistically: accounts computed from real ledgers rather than hand-typed numbers, stock whose every movement can be traced, permissions that protect the data, and an audit log that answers "who did what and when". Feedback and suggestions are welcome through the repository's **Issues** section.

<div align="right"><a href="#english">↑ Back to top</a></div>
