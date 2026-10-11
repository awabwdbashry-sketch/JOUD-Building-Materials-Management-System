&#x20;\<div align="center">

# JOUD | جود لمواد البناء

### Building Materials Management System

**نظام متكامل لإدارة مبيعات ومخزون مواد البناء**

A modern, Arabic-first management system designed to simplify daily operations for building materials businesses, combining inventory control, point of sale, purchases, payments, reporting, and user permissions in one responsive application.

\<p align="center">
&#x20; \<img src="[https://img.shields.io/badge/PHP-Backend-777BB4?style=for-the-badge&logo=php&logoColor=white](https://img.shields.io/badge/PHP-Backend-777BB4?style=for-the-badge\&logo=php\&logoColor=white)" alt="PHP">
&#x20; \<img src="[https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge\&logo=mysql\&logoColor=white)" alt="MySQL">
&#x20; \<img src="[https://img.shields.io/badge/Arabic-RTL-0F766E?style=for-the-badge](https://img.shields.io/badge/Arabic-RTL-0F766E?style=for-the-badge)" alt="Arabic RTL">
&#x20; \<img src="[https://img.shields.io/badge/Desktop-Electron-47848F?style=for-the-badge&logo=electron&logoColor=white](https://img.shields.io/badge/Desktop-Electron-47848F?style=for-the-badge\&logo=electron\&logoColor=white)" alt="Electron">
&#x20; \<img src="[https://img.shields.io/badge/Environment-WAMP-6C5CE7?style=for-the-badge](https://img.shields.io/badge/Environment-WAMP-6C5CE7?style=for-the-badge)" alt="WAMP">
\</p>

\<p align="center">
&#x20; \<a href="#-نظرة-عامة">العربية\</a> ·
&#x20; \<a href="#-english">English\</a> ·
&#x20; \<a href="#-installation">Installation\</a> ·
&#x20; \<a href="#-technology-stack">Technology Stack\</a>
\</p>

---

**JOUD is more than a sales screen. It is a business management solution built around the everyday needs of building materials stores.**

\</div>

## 📑 Table of Contents

### العربية

- [نظرة عامة](#-نظرة-عامة)
- [أهداف المشروع](#-أهداف-المشروع)
- [المميزات الرئيسية](#-المميزات-الرئيسية)
- [المستخدمون والصلاحيات](#-المستخدمون-والصلاحيات)
- [لوحة التحكم والتقارير](#-لوحة-التحكم-والتقارير)
- [التقنيات المستخدمة](#-التقنيات-المستخدمة)
- [متطلبات التشغيل](#-متطلبات-التشغيل)
- [التثبيت والإعداد المحلي](#-التثبيت-والإعداد-المحلي)
- [قاعدة البيانات](#-قاعدة-البيانات)
- [نسخة سطح المكتب](#-نسخة-سطح-المكتب)
- [الأمان والنسخ الاحتياطي](#-الأمان-والنسخ-الاحتياطي)
- [حالة المشروع](#-حالة-المشروع)
- [المطور](#-المطور)

### English

- [Overview](#-english)
- [Key Features](#-key-features)
- [User Roles and Permissions](#-user-roles-and-permissions)
- [Technology Stack](#-technology-stack)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Database Structure](#-database-structure)
- [Desktop Application](#-desktop-application)
- [Security and Backups](#-security-and-backups)
- [Project Structure](#-project-structure)
- [Contributing](#-contributing)
- [Developer](#-developer)

---

# 🇸🇩 العربية

## 📌 نظرة عامة

**جود لمواد البناء (JOUD Building Materials Management System)** هو نظام لإدارة العمليات اليومية لمحلات ومؤسسات مواد البناء، صُمم للمساعدة في تنظيم المبيعات والمخزون والمشتريات والعملاء والموردين والمدفوعات والمصروفات والتقارير من خلال واجهة عربية تدعم اتجاه الكتابة من اليمين إلى اليسار (RTL).

يهدف المشروع إلى تقليل الاعتماد على التسجيل اليدوي المتفرق، وتوفير رؤية أوضح لحركة المنتجات والمبيعات والمبالغ المستحقة، مع تنظيم وصول المستخدمين حسب أدوارهم وصلاحياتهم.

النظام مبني باستخدام PHP وMySQL/MariaDB، ويمكن تشغيله محليًا ضمن بيئة WAMP، مع وجود غلاف لتشغيل التطبيق كبرنامج سطح مكتب باستخدام Electron.

### 🎯 أهداف المشروع

- تنظيم عمليات البيع اليومية في مكان واحد.
- متابعة أرصدة المنتجات وحركة المخزون.
- تسهيل تسجيل المشتريات والتعامل مع الموردين.
- تنظيم حسابات العملاء والمدفوعات والمبالغ المتبقية.
- متابعة المصروفات والمدفوعات المرتبطة بالعمليات.
- توفير تقارير تساعد الإدارة في متابعة أداء النشاط التجاري.
- فصل صلاحيات الإدارة عن صلاحيات الكاشير.
- توفير واجهة عربية متجاوبة للاستخدام على الكمبيوتر والهواتف.

## ✨ المميزات الرئيسية

### 📦 1. إدارة المنتجات والمخزون

إدارة بيانات المنتجات ومتابعة أرصدتها بصورة منظمة.

- إضافة المنتجات وتعديل بياناتها.
- تنظيم المنتجات باستخدام التصنيفات والوحدات.
- تسجيل أسعار المنتجات الأساسية وأسعار البيع الدنيا عند دعمها في إعدادات النظام.
- متابعة الكميات المتوفرة في المخزون.
- تحديد حد أدنى للمخزون والتنبيه إلى المنتجات منخفضة الكمية.
- متابعة تأثير عمليات البيع والشراء على الكميات.
- تسجيل حركات المخزون عند تنفيذ العمليات المدعومة.
- تسهيل الوصول إلى المنتجات من واجهة البيع.

### 🧾 2. المبيعات ونقطة البيع (POS)

واجهة مبيعات مصممة لتسهيل تسجيل عمليات البيع اليومية.

- اختيار المنتجات وإضافتها إلى عملية البيع.
- تحديد الكميات والأسعار وفق صلاحيات المستخدم وإعدادات النظام.
- عرض تفاصيل الأصناف والإجماليات.
- تسجيل طريقة الدفع، مثل النقد أو البنك.
- دعم تسجيل المدفوعات والمبالغ المتبقية بحسب نوع العملية وإعدادات النظام.
- تنظيم بيانات الفاتورة وعناصرها.
- مراعاة صلاحيات الكاشير والمدير عند تنفيذ العمليات الحساسة.
- دعم إدخال صنف يدوي في عمليات البيع حيث تكون هذه الوظيفة متاحة في النسخة المستخدمة.

> **ملاحظة:** إتاحة البيع بسعر أقل من السعر الأدنى، أو البيع الآجل، أو إدخال صنف يدوي تعتمد على إعدادات النظام وصلاحيات الحساب.

### 🛒 3. إدارة المشتريات والموردين

تنظيم عمليات شراء البضائع ومتابعة الموردين.

- تسجيل بيانات الموردين.
- إنشاء سجلات المشتريات.
- تسجيل المنتجات والكميات ضمن المشتريات.
- متابعة تكلفة الشراء وإجماليات العمليات.
- ربط المشتريات بالموردين.
- دعم متابعة المدفوعات المتعلقة بالموردين.
- المساعدة في تتبع الالتزامات المالية الناتجة عن عمليات الشراء.

### 👥 4. إدارة العملاء والمدفوعات

توفير سجل منظم للعملاء ومتابعة التعاملات المالية معهم.

- إضافة بيانات العملاء وإدارتها.
- ربط عمليات البيع بالعملاء عند الحاجة.
- متابعة المبالغ المدفوعة والمتبقية.
- تنظيم تسجيل استلام المدفوعات من العملاء.
- دعم حالات الدفع الكامل أو الجزئي أو الآجل بحسب إعدادات النظام.
- الاحتفاظ بسجلات مرتبطة بالعمليات المالية لتسهيل المراجعة.

### 💰 5. إدارة المدفوعات

يساعد النظام على تنظيم التدفقات المالية المرتبطة بالمبيعات والمشتريات.

- تسجيل طرق الدفع المتاحة.
- دعم النقد والتحويل أو الدفع البنكي وفق إعدادات النظام.
- متابعة المبالغ المسددة والمتبقية.
- تنظيم دفعات العملاء والموردين.
- ربط الدفعات بالعمليات المالية ذات الصلة حيث يدعم النظام ذلك.

### 🧮 6. إدارة المصروفات

تسجيل المصروفات التشغيلية للمؤسسة ومتابعتها.

- إضافة المصروفات وتعديل بياناتها بحسب الصلاحيات.
- تنظيم المصروفات حسب التصنيفات المتاحة.
- تسجيل قيمة المصروف وتاريخه وبياناته.
- الاستفادة من بيانات المصروفات في مراجعة الأداء المالي.
- الاحتفاظ بسجلات المصروفات ضمن قاعدة بيانات النظام.

### 📊 7. لوحة التحكم والتقارير

تمنح لوحة التحكم الإدارة نظرة عامة على النشاط التجاري والبيانات المهمة.

- عرض مؤشرات المبيعات والمعلومات التشغيلية المتاحة.
- متابعة بيانات المنتجات والمخزون.
- إظهار المنتجات التي تحتاج إلى إعادة توفير عند تفعيل مؤشرات المخزون.
- الاطلاع على المعلومات المالية المسجلة في النظام.
- مراجعة تقارير المبيعات والمشتريات والمصروفات بحسب الصفحات المتاحة.
- دعم التصفية الزمنية في التحليلات الإدارية، وفق خيارات التقرير المتاحة.
- تسهيل الانتقال من الملخصات إلى التفاصيل عندما توفر صفحة التقرير ذلك.

**أمثلة على الأسئلة التي تساعد التقارير في الإجابة عنها:**

- ما حجم المبيعات خلال فترة محددة؟
- ما المنتجات التي تم بيعها؟
- ما الكميات المتبقية من المنتجات؟
- ما العمليات المالية التي سُجلت؟
- ما المبالغ التي ما زالت مستحقة؟
- ما المصروفات المسجلة خلال الفترة؟

تعتمد التفاصيل الفعلية المتاحة على إصدار النظام والصفحات المفعلة فيه.

### 🔐 8. المستخدمون والصلاحيات

يعتمد النظام على فصل صلاحيات المستخدمين بحسب الدور الوظيفي.

- حسابات منفصلة للمستخدمين.
- أدوار أساسية تشمل المدير والكاشير.
- تنظيم الوصول إلى الوظائف بحسب الصلاحيات الممنوحة.
- حماية العمليات الحساسة التي تتطلب صلاحيات إضافية.
- دعم صلاحيات مرتبطة بالتحليلات والأسعار الدنيا والمدفوعات وفق إعدادات النظام.
- الاحتفاظ بسجلات تدقيق للعمليات التي يسجلها النظام.

### ⚙️ 9. الإعدادات وسجل التدقيق

- إدارة إعدادات النظام المتاحة من خلال واجهة الإعدادات.
- تنظيم البيانات المرتبطة بتهيئة التطبيق.
- تسجيل بعض الأحداث والعمليات في سجل التدقيق.
- المساعدة في مراجعة الأنشطة المهمة التي تتم داخل النظام.

### 📱 10. واجهة متجاوبة

تم تصميم الواجهة لتناسب بيئة العمل اليومية على أحجام شاشات مختلفة.

- واجهة باللغة العربية.
- دعم اتجاه الكتابة RTL.
- تصميم مناسب لشاشات الكمبيوتر.
- مراعاة استخدام النظام على الهواتف.
- تسهيل الوصول إلى وظائف الإدارة والمبيعات.
- واجهة تعتمد على هوية JOUD البصرية بألوان الكحلي والذهبي ولمسات تصميم حديثة.

---

## 👤 المستخدمون والصلاحيات

يحتوي النظام على دورين أساسيين:

| الدور                 | الوصف                                                                          |
| --------------------- | ------------------------------------------------------------------------------ |
| **المدير (Admin)**    | إدارة النظام ومتابعة البيانات والتقارير واستخدام الوظائف الإدارية المسموح بها. |
| **الكاشير (Cashier)** | تنفيذ عمليات البيع والوظائف التشغيلية التي تسمح بها الصلاحيات الممنوحة للحساب. |

قد تختلف الوظائف المتاحة لكل حساب وفق إعدادات الصلاحيات داخل قاعدة البيانات.

**مبدأ التصميم:** لا ينبغي منح المستخدم صلاحيات إدارية لمجرد أنه يستطيع الوصول إلى واجهة النظام؛ يجب أن تتوافق العمليات المتاحة مع الدور والصلاحيات المحددة له.

## 📊 لوحة التحكم والتقارير

توفر التقارير أساسًا لمراجعة النشاط اليومي واتخاذ قرارات تشغيلية أفضل.

من أمثلة الاستخدام:

- مراجعة المبيعات خلال يوم أو فترة محددة.
- فحص تفاصيل الأصناف داخل عمليات البيع عندما تكون التفاصيل متاحة.
- متابعة تغير أرصدة المنتجات.
- مراجعة المشتريات والمصروفات المسجلة.
- متابعة المبالغ المدفوعة والمتبقية.
- مقارنة المؤشرات المالية بين الفترات التي يدعمها التقرير.

لا تُعد بيانات التقارير بديلًا عن المراجعة المحاسبية، ويجب التأكد من صحة الإدخالات والتسويات قبل الاعتماد عليها في القرارات المالية.

## 🧰 التقنيات المستخدمة

| التقنية             | الاستخدام                                               |
| ------------------- | ------------------------------------------------------- |
| **PHP**             | منطق التطبيق ومعالجة الطلبات على الخادم.                |
| **MySQL / MariaDB** | تخزين بيانات النظام وإدارة العلاقات بين السجلات.        |
| **PDO**             | الاتصال بقاعدة البيانات وتنفيذ الاستعلامات المدعومة به. |
| **HTML5**           | بناء هيكل صفحات الواجهة.                                |
| **CSS3**            | تنسيق الواجهة وتطبيق الهوية البصرية والتجاوب.           |
| **JavaScript**      | التفاعلات داخل الواجهة.                                 |
| **Bootstrap**       | بناء واجهات متجاوبة عند استخدامه في الصفحات.            |
| **Apache / WAMP**   | تشغيل التطبيق محليًا ضمن بيئة خادم الويب.               |
| **Electron**        | توفير غلاف لتشغيل التطبيق في نافذة سطح مكتب.            |

> قد تختلف الإصدارات الفعلية للمكتبات والمتطلبات باختلاف نسخة المشروع. راجع الملفات الموجودة في المستودع قبل تغيير الإصدارات أو تثبيت أي اعتماد إضافي.

## 💻 متطلبات التشغيل

### لتشغيل نسخة الويب محليًا

- نظام Windows أو نظام يدعم بيئة التشغيل المناسبة.
- WAMP أو بيئة Apache وPHP وMySQL/MariaDB مكافئة.
- إصدار PHP متوافق مع الكود المستخدم.
- MySQL أو MariaDB.
- متصفح حديث.
- ملفات المشروع وملف إعدادات قاعدة البيانات.
- ملف SQL الخاص بإنشاء قاعدة البيانات إذا كان المشروع يحتاج إلى تثبيت جديد.

### لتشغيل نسخة سطح المكتب

- Windows.
- ملفات تطبيق JOUD الخاصة بسطح المكتب.
- خدمات PHP وخادم الويب وقاعدة البيانات المطلوبة لتشغيل النسخة المستخدمة، ما لم تكن قد جرى تهيئتها بطريقة أخرى.

**مهم:** وجود ملف تثبيت Windows لا يعني بالضرورة أن التطبيق يحتوي داخله على PHP وApache وMySQL. يعتمد التشغيل على طريقة تجهيز نسخة سطح المكتب.

## 🚀 التثبيت والإعداد المحلي

الخطوات التالية مخصصة لإعداد نسخة محلية جديدة. إذا كان لديك تثبيت قائم يحتوي على بيانات فعلية، فلا تنفذ خطوات إنشاء قاعدة البيانات أو استيرادها قبل التأكد من حالة النظام.

### 1. تنزيل المشروع

يمكنك استنساخ المستودع باستخدام Git:

```bash
git clone https://github.com/awabwdbashry-sketch/JOUD-Building-Materials-Management-System.git
```

أو تنزيل المشروع بصيغة ZIP من GitHub.

### 2. وضع المشروع داخل WAMP

ضع مجلد المشروع داخل مجلد المواقع الخاص بـ WAMP. مثال:

```text
C:\wamp64\www\joud
```

قد يختلف المسار بحسب مكان تثبيت WAMP واسم مجلد المشروع لديك.

### 3. تشغيل خدمات WAMP

شغّل خدمات Apache وMySQL/MariaDB من لوحة تحكم WAMP، وتأكد من أن الخدمات تعمل بصورة صحيحة.

### 4. إعداد قاعدة البيانات

افتح phpMyAdmin وأنشئ قاعدة بيانات باسم:

```text
joud_building
```

هذا هو اسم قاعدة البيانات المستخدم في بيئة التطوير المحلية المذكورة للمشروع، ويمكن أن يختلف في بيئتك.

إذا كانت لديك قاعدة بيانات موجودة بالفعل، **لا تنشئ قاعدة جديدة ولا تعِد استيراد ملف SQL تلقائيًا**؛ فقد يؤدي ذلك إلى تعارض مع البيانات الحالية أو تغييرها.

### 5. إعداد الاتصال بقاعدة البيانات

افتح ملف إعدادات قاعدة البيانات الموجود في المشروع، وحدد إعدادات الاتصال المناسبة لبيئتك، مثل:

- عنوان خادم قاعدة البيانات.
- اسم قاعدة البيانات.
- اسم مستخدم قاعدة البيانات.
- كلمة مرور قاعدة البيانات.
- إعدادات البيئة التي يتطلبها التطبيق.

استخدم أسماء المتغيرات والثوابت الموجودة فعلًا في ملفات المشروع، ولا تضف أسماء إعدادات جديدة دون التحقق من طريقة استخدامها داخل الكود.

لا ترفع كلمات مرور قاعدة البيانات أو بيانات الدخول الحقيقية إلى مستودع عام.

### 6. تجهيز الجداول

في تثبيت جديد، استخدم ملف SQL المرفق بالمشروع إذا كان موجودًا ومخصصًا لإنشاء قاعدة البيانات.

تأكد من أن الملف مناسب لإصدار المشروع الحالي، وأنك تعمل على قاعدة البيانات الصحيحة قبل الاستيراد.

### 7. فتح التطبيق

إذا كان اسم المجلد `joud` داخل مجلد `www`، فقد يكون عنوان التشغيل المحلي:

```text
http://localhost/joud/
```

يعتمد العنوان النهائي على إعدادات Apache وطريقة تنظيم ملفات المشروع.

### 8. اختبار النظام

بعد نجاح الاتصال بقاعدة البيانات، تحقق من الأمور الأساسية:

- فتح صفحة تسجيل الدخول.
- تسجيل الدخول باستخدام حساب صالح تم إعداده مسبقًا.
- ظهور لوحة التحكم.
- فتح صفحات المنتجات والمبيعات.
- التحقق من قراءة البيانات وحفظها.
- تجربة الصلاحيات بحسابات مختلفة.
- التأكد من عدم ظهور أخطاء PHP أو أخطاء اتصال قاعدة البيانات.

لا تفترض وجود حساب مدير افتراضي أو كلمة مرور ثابتة ما لم تكن موثقة وموجودة فعلًا في نسخة المشروع.

---

## 🗄️ قاعدة البيانات

يعتمد JOUD على قاعدة بيانات علائقية لتنظيم بيانات المبيعات والمخزون والعملاء والموردين والمدفوعات والمستخدمين.

تتضمن بنية النظام جداول مخصصة لمجموعات البيانات التالية:

| الجدول                | الغرض                                  |
| --------------------- | -------------------------------------- |
| `users`               | بيانات المستخدمين.                     |
| `roles`               | أدوار المستخدمين.                      |
| `permissions`         | تعريف الصلاحيات.                       |
| `role_permissions`    | ربط الأدوار بالصلاحيات.                |
| `products`            | بيانات المنتجات.                       |
| `categories`          | تصنيفات المنتجات.                      |
| `units`               | وحدات القياس.                          |
| `customers`           | بيانات العملاء.                        |
| `suppliers`           | بيانات الموردين.                       |
| `sales`               | سجلات عمليات البيع.                    |
| `sale_items`          | الأصناف المرتبطة بعمليات البيع.        |
| `purchases`           | سجلات المشتريات.                       |
| `purchase_items`      | الأصناف المرتبطة بالمشتريات.           |
| `payments`            | سجلات المدفوعات.                       |
| `payment_allocations` | تخصيص المدفوعات للعمليات المرتبطة بها. |
| `supplier_payments`   | مدفوعات الموردين.                      |
| `expenses`            | المصروفات.                             |
| `expense_categories`  | تصنيفات المصروفات.                     |
| `inventory_movements` | حركات المخزون.                         |
| `payment_methods`     | طرق الدفع.                             |
| `settings`            | إعدادات النظام.                        |
| `audit_logs`          | سجلات التدقيق.                         |

> القائمة توضح أسماء الجداول المعروفة ضمن تصميم المشروع، وليست بديلًا عن فحص ملف SQL الفعلي. قد تختلف بعض الجداول أو الأعمدة بحسب إصدار قاعدة البيانات المستخدم.

### ⚠️ تنبيه مهم حول قاعدة البيانات

قبل تنفيذ أي تغيير في قاعدة البيانات:

1. تأكد من اسم قاعدة البيانات التي يتصل بها التطبيق.
2. أنشئ نسخة احتياطية عند العمل على بيانات حقيقية.
3. افحص ملف SQL أو التحديث المطلوب قبل تنفيذه.
4. لا تحذف الجداول أو تعِد استيراد قاعدة البيانات لمجرد ظهور خطأ في صفحة واحدة.
5. اختبر التغييرات على نسخة منفصلة عند الإمكان.

## 🖥️ نسخة سطح المكتب

يوفر المشروع غلافًا لتشغيل JOUD كتطبيق على Windows باستخدام Electron.

تتضمن ملفات نسخة سطح المكتب المعروفة:

| الملف            | الاستخدام                                             |
| ---------------- | ----------------------------------------------------- |
| `JOUD-Setup.exe` | برنامج تثبيت نسخة سطح المكتب.                         |
| `JOUD.exe`       | نسخة تشغيل مباشر (Portable) بحسب طريقة تجهيز الإصدار. |
| `desktop/`       | ملفات مشروع غلاف سطح المكتب.                          |

### ما الفرق بين نسخة التثبيت والنسخة المحمولة؟

**JOUD-Setup.exe — Installer**

- يُستخدم لتثبيت التطبيق على الكمبيوتر.
- يوفر طريقة تشغيل معتادة من بيئة Windows.
- قد ينشئ اختصارات وفق إعدادات حزمة التثبيت.
- هو الخيار المناسب عند الرغبة في تثبيت التطبيق بالطريقة التقليدية.

**JOUD.exe — Portable**

- يُقصد به التشغيل المباشر دون تنفيذ عملية التثبيت المعتادة.
- يمكن أن يكون مناسبًا للتجربة أو التشغيل من مجلد مستقل.
- لا يعني بالضرورة أن التطبيق مستقل تمامًا عن WAMP أو قاعدة البيانات أو إعدادات البيئة.

### ملاحظة حول بنية التشغيل

قد تعمل واجهة Electron في نافذة منفصلة، بينما يستمر PHP وApache وMySQL/MariaDB في العمل عبر خدمات WAMP.

لذلك، قبل نقل التطبيق إلى كمبيوتر آخر، تحقق من أن خدمات التشغيل المطلوبة وإعدادات قاعدة البيانات متوفرة عليه. لا تفترض أن نسخ ملف EXE وحده يكفي لنقل قاعدة البيانات أو جميع مكونات الخادم.

## 🔒 الأمان والنسخ الاحتياطي

عند استخدام النظام في بيئة عمل حقيقية، يُنصح بالاهتمام بالنقاط التالية:

- استخدام كلمات مرور قوية لحسابات المستخدمين.
- تخصيص حساب مستقل لكل مستخدم.
- منح أقل قدر من الصلاحيات اللازمة لإنجاز العمل.
- عدم مشاركة بيانات المدير مع حسابات الكاشير.
- حماية ملفات إعدادات الاتصال بقاعدة البيانات.
- عدم نشر بيانات الدخول أو النسخ الاحتياطية في مستودع GitHub عام.
- استخدام الاستعلامات المجهزة عند التعامل مع مدخلات المستخدم.
- التحقق من صلاحيات المستخدم قبل تنفيذ العمليات الحساسة.
- حماية صفحات الإدارة من الوصول غير المصرح به.
- إجراء نسخ احتياطية دورية لقاعدة البيانات.
- اختبار استعادة النسخ الاحتياطية بدلًا من الاكتفاء بإنشائها.
- استخدام HTTPS عند نشر التطبيق عبر الإنترنت.
- تحديث بيئة PHP وقاعدة البيانات بما يتوافق مع متطلبات التطبيق.

### 📦 النسخ الاحتياطي

قبل أي تحديث مهم أو تعديل على الجداول:

1. خذ نسخة احتياطية من قاعدة البيانات.
2. احتفظ بنسخة من ملفات المشروع الحالية.
3. نفّذ التغيير بعد التحقق من البيئة المستهدفة.
4. اختبر الوظائف الأساسية بعد التحديث.
5. احتفظ بإمكانية الرجوع إلى النسخة السابقة إذا ظهرت مشكلة.

**تنبيه:** مستودع GitHub ليس بديلًا عن النسخ الاحتياطي لقاعدة البيانات التشغيلية. يجب حفظ بيانات العمل الفعلية في مكان آمن ومناسب.

## 🌐 النشر على الإنترنت

تم بناء المشروع باستخدام PHP وقاعدة بيانات، ولذلك يحتاج إلى استضافة تدعم تقنيات الخادم المطلوبة.

قبل النشر، تحقق من:

- توافق إصدار PHP مع المشروع.
- توفر MySQL أو MariaDB.
- صحة إعدادات الاتصال بقاعدة البيانات.
- ضبط متغيرات البيئة والثوابت المطلوبة.
- إعداد الصلاحيات المناسبة للملفات والمجلدات.
- حماية ملفات الإعدادات والبيانات الحساسة.
- إعداد HTTPS.
- مراجعة سجلات الأخطاء.
- إعداد نسخ احتياطية منتظمة.

**GitHub Pages وحده لا يشغّل تطبيق PHP أو قاعدة بيانات MySQL.** يمكن استخدام GitHub لحفظ الكود ومراجعته، بينما يحتاج تشغيل التطبيق إلى خادم متوافق.

## 🧪 الاختبار والتحقق

عند إعداد إصدار جديد، يُنصح بمراجعة الوظائف التالية:

- [ ] تشغيل التطبيق والاتصال بقاعدة البيانات.
- [ ] تسجيل الدخول والخروج.
- [ ] التحقق من صلاحيات المدير والكاشير.
- [ ] إضافة المنتجات وتعديلها.
- [ ] مراجعة كميات المخزون.
- [ ] تنفيذ عملية بيع تجريبية.
- [ ] التأكد من تسجيل الأصناف داخل عملية البيع.
- [ ] التحقق من تسجيل طريقة الدفع.
- [ ] مراجعة حالات الدفع الجزئي أو الآجل إذا كانت مفعلة.
- [ ] تسجيل عملية شراء تجريبية عند توفر الوظيفة.
- [ ] مراجعة بيانات العملاء والموردين.
- [ ] اختبار تسجيل المصروفات.
- [ ] مراجعة التقارير.
- [ ] اختبار عرض الواجهة على شاشة هاتف.
- [ ] مراجعة سجل التدقيق حيث يكون متاحًا.
- [ ] التحقق من عدم ظهور أخطاء في سجلات الخادم.

هذه قائمة تحقق مقترحة للاختبار، ولا تعني أن كل اختبار نُفذ على كل بيئة أو إصدار.

## 📁 Project Structure

قد تختلف التفاصيل الدقيقة للمجلدات والملفات بين الإصدارات، لكن التنظيم العام للمشروع يتضمن مكونات من هذا النوع:

```text
JOUD-Building-Materials-Management-System/
│
├── README.md
│
├── config/
│   └── Application and database configuration
│
├── includes/
│   └── Shared application components
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── desktop/
│   ├── main.js
│   ├── package.json
│   ├── lib.js
│   └── build-windows.bat
│
├── Database SQL files
├── Application PHP pages
└── Other project resources
```

> **مهم:** هذا مخطط توضيحي للمكونات، وليس جردًا حرفيًا لكل ملف في المستودع. راجع شجرة الملفات الفعلية قبل الاعتماد عليه لتحديد مسارات التثبيت أو التشغيل.

## 🗺️ حالة المشروع

يهدف المشروع إلى توفير نظام عملي لإدارة عمليات متجر مواد البناء، مع دعم واجهة عربية وقاعدة بيانات ونسخة سطح مكتب.

تختلف جاهزية كل وظيفة بحسب إصدار الكود المستخدم، لذلك يُنصح بمراجعة النسخة الموجودة في المستودع واختبار العمليات المهمة قبل اعتمادها في بيئة تشغيل فعلية.

## 🤝 المساهمة في المشروع

يمكنك المساهمة في تحسين المشروع من خلال:

1. مراجعة الكود الحالي وفهم بنيته.
2. تحديد المشكلة أو التحسين المطلوب بوضوح.
3. إنشاء فرع مستقل للتعديل.
4. اختبار التغيير قبل رفعه.
5. توثيق أي تغيير في قاعدة البيانات أو متطلبات التشغيل.
6. إرسال Pull Request يوضح الهدف من التغيير وأثره.

يُرجى تجنب التغييرات التي تؤدي إلى حذف بيانات موجودة أو تغيير سلوك العمليات المالية دون مراجعة واختبار مناسبين.

## 📜 الترخيص

لم يتم تحديد ترخيص قانوني هنا بصورة مستقلة. راجع ملف `LICENSE` في المستودع إن كان موجودًا، أو حدّد ترخيصًا مناسبًا قبل السماح للآخرين بإعادة استخدام الكود أو توزيعه.

وجود المشروع على GitHub لا يعني تلقائيًا أن استخدامه أو إعادة توزيعه مسموحان دون قيود.

---

## 👨‍💻 المطور

**Awab Ibrahim Bashry — AwabBuilds**

Software Developer | Web & Flutter Developer | AI Developer

مهتم بتطوير تطبيقات الويب والموبايل والذكاء الاصطناعي، وبناء حلول برمجية عملية تجمع بين سهولة الاستخدام والتنظيم وجودة التنفيذ.

- **GitHub:** [awabwdbashry-sketch](https://github.com/awabwdbashry-sketch)
- **Portfolio:** [awabdev.byethost12.com](https://awabdev.byethost12.com/)

---

# 🇬🇧 English

## 📌 Overview

**JOUD Building Materials Management System** is a business management application designed to organize everyday operations for building materials stores. It brings together product and inventory management, sales, purchases, customer and supplier records, payments, expenses, reporting, and role-based access within an Arabic-first, right-to-left interface.

The project is built with PHP and MySQL/MariaDB and can be operated locally in a WAMP environment. An Electron-based desktop wrapper is also provided for a Windows desktop experience.

### 🎯 Project Goals

- Centralize daily sales and operational records.
- Improve visibility into product quantities and inventory movements.
- Organize purchasing activities and supplier records.
- Track customer payments and outstanding balances.
- Record operating expenses.
- Provide reporting tools for business monitoring.
- Separate administrative access from cashier operations.
- Support responsive Arabic RTL user interfaces.

## ✨ Key Features

### 📦 Product and Inventory Management

- Create and manage product records.
- Organize products by categories and units.
- Maintain product pricing information, including configured minimum prices where supported.
- Track available stock quantities.
- Configure minimum-stock thresholds.
- Monitor inventory changes associated with supported operations.
- Record inventory movements where implemented.
- Make products available to the sales interface.

### 🧾 Sales and Point of Sale (POS)

- Select products and quantities for a sale.
- Calculate line totals and sale totals.
- Apply pricing rules and user permissions.
- Record supported payment methods, including cash and bank payments.
- Track paid and outstanding amounts when supported by the selected payment workflow.
- Organize sale records and their associated items.
- Support manual sale items where the deployed version provides that feature.

Price overrides, credit sales, and other restricted actions depend on application configuration and the permissions assigned to the user.

### 🛒 Purchases and Supplier Management

- Maintain supplier records.
- Record purchase transactions.
- Associate products and quantities with purchases.
- Track purchase totals and related information.
- Organize supplier-related payments.
- Maintain purchasing records for operational review.

### 👥 Customer Management and Payments

- Maintain customer records.
- Associate sales with customers when required.
- Track recorded payments and outstanding balances.
- Record customer payment receipts where supported.
- Support full, partial, or credit payment workflows when enabled.

### 💰 Payment Management

- Organize supported payment methods.
- Record cash and bank-related payments according to system configuration.
- Track paid and remaining amounts.
- Manage customer and supplier payment records.
- Associate payments with relevant financial transactions where supported.

### 🧮 Expense Management

- Record operating expenses.
- Organize expenses using available categories.
- Maintain expense amounts, dates, and related details.
- Review recorded expenses as part of business reporting.

### 📊 Dashboard and Reporting

- Review available sales and operational indicators.
- Monitor product and inventory information.
- Identify low-stock products when the corresponding indicators are enabled.
- Review recorded sales, purchases, and expenses.
- Track paid and outstanding amounts.
- Use supported date filters and reporting periods.
- Inspect transaction details on report pages where available.

The exact report fields and drill-down capabilities depend on the deployed application version.

### 🔐 Users and Permissions

- Separate user accounts.
- Core roles for administrators and cashiers.
- Role-based access to application features.
- Additional permissions for restricted operations where configured.
- Audit records for supported application events.

### ⚙️ Settings and Audit Logs

- Manage available system settings.
- Maintain application configuration records.
- Review recorded audit events.
- Support administrative review of important activities.

### 📱 Responsive Arabic Interface

- Arabic-first user experience.
- Right-to-left layout.
- Desktop-oriented administration and sales workflows.
- Responsive layouts for smaller screens.
- JOUD visual identity using navy and gold accents.

## 👤 User Roles and Permissions

| Role              | Description                                                                                            |
| ----------------- | ------------------------------------------------------------------------------------------------------ |
| **Administrator** | Access to administrative functions, reporting, configuration, and other permitted management features. |
| **Cashier**       | Access to sales and operational functions granted by the assigned permissions.                         |

Actual access is controlled by the application's implemented authorization rules and database configuration.

## 🧰 Technology Stack

| Technology      | Purpose                                                |
| --------------- | ------------------------------------------------------ |
| PHP             | Server-side application logic.                         |
| MySQL / MariaDB | Relational database.                                   |
| PDO             | Database connectivity and prepared queries where used. |
| HTML5           | Page structure.                                        |
| CSS3            | Layout and visual styling.                             |
| JavaScript      | Client-side interactions.                              |
| Bootstrap       | Responsive UI components where used.                   |
| Apache / WAMP   | Local web server environment.                          |
| Electron        | Windows desktop application wrapper.                   |

Refer to the actual repository files for the precise dependency versions and runtime requirements.

## 💻 Requirements

### Web Application

- A compatible PHP runtime.
- Apache or another compatible web server.
- MySQL or MariaDB.
- A modern web browser.
- The project files.
- Database configuration and SQL setup files, where required.

### Desktop Application

- Windows.
- The JOUD desktop application files.
- The PHP, web-server, and database services required by the deployed version, unless they have been packaged or configured separately.

An Electron executable does not automatically imply that the entire backend and database are bundled into the application.

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/awabwdbashry-sketch/JOUD-Building-Materials-Management-System.git
```

Alternatively, download the repository as a ZIP archive from GitHub.

### 2. Place the Project in WAMP

For example:

```text
C:\wamp64\www\joud
```

Use the actual installation path on your computer.

### 3. Start the Required Services

Start Apache and MySQL/MariaDB from the WAMP control panel and confirm that both services are running.

### 4. Configure the Database

The local development database has been referred to as:

```text
joud_building
```

Create this database only when setting up a new installation or when your deployment instructions require it.

If an existing database contains operational data, do not import a fresh schema over it without first reviewing the migration requirements and creating a backup.

### 5. Configure Database Connectivity

Locate the application's existing database configuration file and set the appropriate host, database name, username, password, and other required settings.

Use the configuration names actually expected by the application. Do not commit real credentials to a public repository.

### 6. Initialize the Schema

For a fresh installation, use the SQL file provided by the project, if available, and verify that it matches the application version.

Do not assume that a fresh-install SQL file is safe to run against an existing production database.

### 7. Open the Application

If the project is served from `C:\wamp64\www\joud`, the local URL may be:

```text
http://localhost/joud/
```

The final URL depends on your Apache configuration and project entry point.

### 8. Verify the Installation

- Confirm that the login page loads.
- Sign in with a valid account configured for your environment.
- Confirm that the dashboard opens.
- Check that database-backed pages load correctly.
- Test product and sales workflows.
- Verify role permissions.
- Review server logs for PHP and database errors.

Do not assume a default administrator password exists unless it is explicitly documented for your release.

## 🗄️ Database Structure

The application uses relational tables to organize users, products, sales, purchases, payments, expenses, and related records.

Known table names include:

`users`, `roles`, `permissions`, `role_permissions`, `products`, `categories`, `units`, `customers`, `suppliers`, `sales`, `sale_items`, `purchases`, `purchase_items`, `payments`, `payment_allocations`, `supplier_payments`, `expenses`, `expense_categories`, `inventory_movements`, `payment_methods`, `settings`, and `audit_logs`.

This is a reference list, not a replacement for inspecting the actual SQL schema. Table names and fields may vary across versions.

### Database Safety

- Confirm the active database before executing SQL.
- Back up important data before schema changes.
- Inspect migration scripts before running them.
- Avoid dropping tables or re-importing the database to fix an unrelated application error.
- Test significant database changes against a separate copy when possible.

## 🖥️ Desktop Application

The project includes an Electron wrapper for running JOUD in a Windows desktop window.

| File or directory | Purpose                                                                       |
| ----------------- | ----------------------------------------------------------------------------- |
| `JOUD-Setup.exe`  | Desktop application installer.                                                |
| `JOUD.exe`        | Direct-launch / portable-style executable, depending on the packaged release. |
| `desktop/`        | Electron wrapper and build-related files.                                     |

### Installer vs. Portable Executable

**Installer — `JOUD-Setup.exe`**

Installs the desktop application using the supplied Windows installation package.

**Portable — `JOUD.exe`**

Is intended for direct launching without the usual installation process, depending on the build configuration.

The portable executable may still depend on the WAMP services, PHP runtime, database, or configuration used by the application. Confirm these requirements before moving the program to another computer.

## 🔒 Security and Backups

For real business use:

- Use strong passwords.
- Give each user a separate account.
- Apply the principle of least privilege.
- Keep database credentials out of public repositories.
- Validate user input.
- Use prepared statements where applicable.
- Verify permissions before sensitive operations.
- Protect administrative pages.
- Use HTTPS for internet-facing deployments.
- Maintain regular database backups.
- Test backup restoration.
- Review application and server logs.
- Keep runtime components compatible and appropriately maintained.

A GitHub repository is not a substitute for backing up the live business database.

## 🌐 Deployment

JOUD requires a hosting environment capable of running PHP and connecting to MySQL/MariaDB.

Before deploying, verify:

- PHP version compatibility.
- Database availability and connectivity.
- Correct application configuration.
- File and directory permissions.
- Protection of secrets and configuration files.
- HTTPS configuration.
- Error logging.
- Backup and recovery procedures.

**GitHub Pages cannot directly run a PHP application or host its MySQL database.** Use GitHub for source-code hosting and a compatible server for application execution.

## 🧪 Testing Checklist

Before using a release in a live business environment, test:

- [ ] Application startup and database connectivity.
- [ ] Login and logout.
- [ ] Administrator and cashier permissions.
- [ ] Product creation and editing.
- [ ] Inventory quantity updates.
- [ ] A sample sale.
- [ ] Sale-item records.
- [ ] Payment method handling.
- [ ] Partial or credit payments, if enabled.
- [ ] Purchase records, where supported.
- [ ] Customer and supplier workflows.
- [ ] Expense records.
- [ ] Reports and transaction details.
- [ ] Mobile layout.
- [ ] Audit logs where available.
- [ ] Server logs for errors.

This checklist is a recommended verification process, not a claim that every item has been tested on every environment.

## 📁 Project Structure

The following is an illustrative overview of the project components. Verify the repository's actual tree for exact paths.

```text
JOUD-Building-Materials-Management-System/
│
├── README.md
├── config/
├── includes/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── desktop/
│   ├── main.js
│   ├── package.json
│   ├── lib.js
│   └── build-windows.bat
├── Database SQL files
├── Application PHP pages
└── Other project resources
```

## 🗺️ Project Status

JOUD is designed to bring core building materials store workflows into one application, with an Arabic RTL interface, relational database, and desktop wrapper.

Review the current repository and test the required workflows before relying on a particular feature in a live environment.

## 🤝 Contributing

Contributions and improvements are welcome.

1. Review the existing implementation.
2. Identify the issue or proposed improvement.
3. Create a separate branch for your changes.
4. Test the changes before submitting them.
5. Document database migrations and runtime changes.
6. Submit a Pull Request explaining the change and its impact.

Avoid changes that could unintentionally delete business data or alter financial workflows without appropriate review and testing.

## 📜 License

A specific open-source license has not been established in this README. Check whether a `LICENSE` file exists in the repository, and choose an appropriate license before authorizing reuse or redistribution.

Publishing code on GitHub does not, by itself, grant unrestricted permission to reuse or redistribute it.

## 👨‍💻 Developer

**Awab Ibrahim Bashry — AwabBuilds**

Software Developer | Web & Flutter Developer | AI Developer

Focused on building practical web, mobile, and AI applications with attention to usability, maintainability, and modern interface design.

- **GitHub:** [awabwdbashry-sketch](https://github.com/awabwdbashry-sketch)
- **Portfolio:** [awabdev.byethost12.com](https://awabdev.byethost12.com/)

---

\<div align="center">

**JOUD — Organizing Your Business, One Transaction at a Time.**

**جود — تنظيم أدق، متابعة أوضح، وإدارة أفضل.**

Built with dedication by **AwabBuilds**

\</div>
