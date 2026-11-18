/* BlueDesk — Main Script: i18n, nav, animations, form */
(function () {
  'use strict';

  const enableDeferredStylesheets = () => {
    document.querySelectorAll('link[data-async-stylesheet]').forEach((stylesheet) => {
      const activate = () => { stylesheet.media = 'all'; };
      if (stylesheet.sheet) {
        activate();
      } else {
        stylesheet.addEventListener('load', activate, { once: true });
        stylesheet.addEventListener('error', activate, { once: true });
      }
    });
  };
  enableDeferredStylesheets();

  /* ---------- i18n dictionary ---------- */
  const I18N = {
    ar: {
      'nav.home': 'الرئيسية',
      'nav.about': 'من نحن',
      'nav.services': 'خدماتنا',
      'nav.portfolio': 'أعمالنا',
      'nav.contact': 'اتصل بنا',
      'nav.cta': 'احصل على عرض سعر',
      'skip.link': 'تخطي إلى المحتوى الرئيسي',
      'hero.badge': 'شركة برمجيات مصرية · حلول برمجية متكاملة',
      'hero.h1a': 'نبني حلولًا برمجية',
      'hero.h1b': 'تدفع',
      'hero.h1c': 'مشروعك',
      'hero.h1d': 'للأمام',
      'hero.lead': 'شركة بلوديسك شركة برمجيات مصرية متخصصة في تطوير المواقع وتطبيقات الجوال وأنظمة الأعمال والسحابة والذكاء الاصطناعي. نحول أفكارك إلى منتجات رقمية احترافية.',
      'hero.cta1': 'ابدأ مشروعك الآن',
      'hero.cta2': 'استعرض أعمالنا',
      'hero.stat1': 'مشروع منجز',
      'hero.stat2': 'عميل سعيد',
      'hero.stat3': 'سنة خبرة',
      'hero.stat4': 'مطور محترف',
      'hero.f1': 'تطوير الويب',
      'hero.f2': 'تطبيقات الجوال',
      'hero.f3': 'حلول سحابية',
      'services.tag': 'خدماتنا',
      'services.title': 'حلول برمجية متكاملة لكل احتياج',
      'services.desc': 'من الفكرة إلى الإطلاق، نقدم خدمات برمجية شاملة تغطي كل مراحل التحول الرقمي لشركتك.',
      'svc1.t': 'تطوير مواقع الويب',
      'svc1.d': 'مواقع وتطبيقات ويب سريعة ومتجاوبة بتصميم عصري يجذب عملاءك ويحقق أعلى معايير الجودة.',
      'svc2.t': 'تطبيقات الجوال',
      'svc2.d': 'تطبيقات أندرويد وiOS بأداء عالٍ وتجربة استخدام سلسة تجعل عملاءك يعودون دائمًا.',
      'svc3.t': 'أنظمة إدارة الأعمال',
      'svc3.d': 'أنظمة ERP وCRM مخصصة تُنظّم عمليات شركتك وتزيد إنتاجيتك وكفاءة فرق العمل.',
      'svc4.t': 'الحوسبة السحابية',
      'svc4.d': 'استضافة ونشر وتوسعة بنية تحتية سحابية آمنة وموثوقة على AWS وGoogle Cloud.',
      'svc5.t': 'الذكاء الاصطناعي',
      'svc5.d': 'حلول ذكية تعتمد على تعلم الآلة ومعالجة اللغة الطبيعية لأتمتة القرارات وتحليل البيانات.',
      'svc6.t': 'التجارة الإلكترونية',
      'svc6.d': 'متاجر إلكترونية متكاملة بأنظمة دفع وشحن ولوحات تحكم تزيد مبيعاتك عبر الإنترنت.',
      'svc.more': 'التفاصيل',
      'why.tag': 'لماذا بلوديسك',
      'why.title': 'شريك تقني تثق به لنجاح رقمي حقيقي',
      'why.desc': 'نجمع بين الخبرة التقنية العميقة والفهم العميق لسوق الأعمال المصري لنقدم حلولًا عملية ونتائج قابلة للقياس.',
      'why.1.t': 'خبرة تقنية متعمقة',
      'why.1.d': 'فريق من المطورين المحترفين ذوي الخبرة في أحدث التقنيات وأفضل الممارسات العالمية.',
      'why.2.t': 'تسليم في المواعيد',
      'why.2.d': 'نلتزم بالجداول الزمنية ونسلّم المشاريع في الوقت المحدد دون التضحية بالجودة.',
      'why.3.t': 'دعم فني مستمر',
      'why.3.d': 'فريق دعم فني متاح على مدار الساعة لضمان استمرارية عمل أنظمتك بلا انقطاع.',
      'why.4.t': 'أسعار تنافسية',
      'why.4.d': 'حلول برمجية عالية الجودة بأسعار عادلة تناسب الشركات الناشئة والمؤسسات الكبرى.',
      'why.badge.n': '98%',
      'why.badge.t': 'رضا العملاء',
      'work.tag': 'أعمالنا',
      'work.title': 'مشاريع نفخر بإنجازها',
      'work.desc': 'نماذج من المشاريع التي أنجزناها لعملائنا في مختلف القطاعات.',
      'work.all': 'عرض كل الأعمال',
      'work.demo': 'تجربة المشروع',
      'work.details': 'عرض التفاصيل',
      'work.back': 'العودة إلى الأعمال',
      'project.overview': 'عن المشروع',
      'project.tech': 'التقنيات المستخدمة',
      'project.gallery': 'صور من المشروع',
      'project.notfound': 'المشروع غير موجود أو لم يعد منشورًا.',
      'project.image.viewer': 'عارض صور المشروع',
      'project.image.close': 'إغلاق الصورة',
      'project.image.prev': 'الصورة السابقة',
      'project.image.next': 'الصورة التالية',
      'branches.tag': 'فروعنا',
      'branches.title': 'مقرّان في خدمتك: قليوب والدقي',
      'branches.desc': 'تفضل بزيارتنا في المقر الرئيسي أو الفرع الثاني، أو تواصل معنا هاتفيًا في أي وقت.',
      'branches.open_map': 'فتح في خرائط جوجل',
      'branches.call': 'اتصل بنا',
      'work.p1.t': 'منصة تجارة إلكترونية',
      'work.p1.d': 'متجر إلكتروني متكامل مع نظام دفع وشحن ولوحة تحكم متقدمة.',
      'work.p1.tag': 'تجارة إلكترونية',
      'work.p2.t': 'نظام إدارة المستشفيات',
      'work.p2.d': 'نظام متكامل لإدارة المواعيد والملفات الطبية والمخزون.',
      'work.p2.tag': 'أنظمة طبية',
      'work.p3.t': 'تطبيق توصيل طعام',
      'work.p3.d': 'تطبيق موبايل للتوصيل مع تتبع مباشر ولوحة تحكم للمطاعم.',
      'work.p3.tag': 'تطبيق موبايل',
      'work.p4.t': 'منصة تعليمية إلكترونية',
      'work.p4.d': 'منصة تعليم عن بعد مع فصول مباشرة وتسجيلات واختبارات تفاعلية.',
      'work.p4.tag': 'تعليم إلكتروني',
      'work.p5.t': 'لوحة تحليلات وبيانات',
      'work.p5.d': 'لوحة معلومات ذكية لتحليل البيانات واتخاذ القرارات لحظيًا.',
      'work.p5.tag': 'ذكاء اصطناعي',
      'work.p6.t': 'موقع مؤسسي لشركة استشارات',
      'work.p6.d': 'موقع احترافي متعدد اللغات محسّن لمحركات البحث بلوحة تحكم.',
      'work.p6.tag': 'تطوير ويب',
      'portfolio.filter.all': 'الكل',
      'portfolio.filter.web': 'تطوير ويب',
      'portfolio.filter.mobile': 'تطبيقات',
      'portfolio.filter.systems': 'أنظمة',
      'portfolio.filter.ai': 'ذكاء اصطناعي',
      'process.tag': 'منهجيتنا',
      'process.title': 'كيف نعمل معك خطوة بخطوة',
      'process.desc': 'عملية واضحة ومنظمة تضمن تحقيق أفضل النتائج لمشروعك.',
      'pr.1.t': 'الاكتشاف والتحليل',
      'pr.1.d': 'نفهم احتياجك بدقة ونحلل السوق والمنافسين لتحديد أهداف المشروع.',
      'pr.2.t': 'التصميم والتطوير',
      'pr.2.d': 'نصمم واجهات جميلة ونطور أنظمة قوية بأحدث التقنيات وبجودة عالية.',
      'pr.3.t': 'الاختبار والإطلاق',
      'pr.3.d': 'نختبر كل جانب بدقة ثم نطلق المشروع بأعلى جودة وأقل مخاطر.',
      'pr.4.t': 'الدعم والتطوير',
      'pr.4.d': 'نبقى معك بعد الإطلاق لدعم وتطوير منتجك وتحقيق نمو مستمر.',
      'test.tag': 'آراء عملائنا',
      'test.title': 'ماذا يقول عملاؤنا عنا',
      'test.desc': 'قصص نجاح حقيقية من عملاء وثقوا بنا وحققوا نتائج استثنائية.',
      'test.1.q': 'فريق بلوديسك بنى لنا منصة تجارة إلكترونية زادت مبيعاتنا 300% خلال سنة واحدة. احترافية عالية والتزام كامل بالمواعيد.',
      'test.1.n': 'أحمد الشريف',
      'test.1.r': 'الرئيس التنفيذي، متجر النخبة',
      'test.2.q': 'الذكاء الاصطناعي الذي طوروه لنا أتمت عمليات خدمة العملاء وقلّص زمن الاستجابة إلى أقل من ثانية. تجربة رائعة.',
      'test.2.n': 'سارة محمد',
      'test.2.r': 'مديرة التحول الرقمي، بنك المستقبل',
      'test.3.q': 'من أول اجتماع إلى الإطلاق، كان الفريق منظمًا ومحترفًا. نظام ERP الذي بناه غيّر طريقة عمل شركتنا بالكامل.',
      'test.3.n': 'خالد العتيبي',
      'test.3.r': 'المدير العملي، مجموعة الفجر',
      'cta.title': 'جاهز لبدء مشروعك الرقمي؟',
      'cta.desc': 'تواصل معنا اليوم واحصل على استشارة مجانية وعرض سعر مخصص لمشروعك.',
      'cta.btn': 'تواصل معنا الآن',
      'footer.desc': 'شركة برمجيات مصرية متخصصة في تقديم حلول برمجية متكاملة تشمل تطوير الويب والتطبيقات والأنظمة والسحابة والذكاء الاصطناعي.',
      'footer.links': 'روابط سريعة',
      'footer.services': 'خدماتنا',
      'footer.contact': 'معلومات التواصل',
      'footer.rights': 'جميع الحقوق محفوظة',
      'footer.made': 'صُنع بحب في مصر',
      'about.title': 'من نحن',
      'about.desc': 'تعرّف على شركة بلوديسك وقصة نجاحنا وقيمنا التي نعمل بها كل يوم.',
      'about.h2': 'شريكك التقني الموثوق في رحلة التحول الرقمي',
      'about.p1': 'شركة بلوديسك (BlueDesk) شركة برمجيات مصرية متخصصة في تطوير الحلول البرمجية المتكاملة. تأسست بهدف تمكين الشركات من التحول الرقمي من خلال منتجات تقنية عالية الجودة.',
      'about.p2': 'نؤمن أن التكنولوجيا يجب أن تكون في متناول الجميع، لذلك نقدم حلولًا برمجية بأسعار عادلة وجودة عالمية، مدعومة بفريق من أفضل المطورين والمصممين والمهندسين في مصر.',
      'about.ck1': 'أكثر من 150 مشروع برمجي منجز',
      'about.ck2': 'فريق من 40+ مطورًا ومهندسًا محترفًا',
      'about.ck3': 'دعم فني على مدار الساعة',
      'about.ck4': 'أسعار تنافسية وجودة عالمية',
      'values.title': 'قيمنا',
      'values.tag': 'قيمنا',
      'values.desc': 'المبادئ التي نعمل بها ونلتزم بها في كل مشروع ننفذه.',
      'val.1.t': 'الجودة',
      'val.1.d': 'لا نقبل بأي شيء أقل من الأفضل. كل سطر كود نكتبه يخضع لمعايير جودة صارمة.',
      'val.2.t': 'الابتكار',
      'val.2.d': 'نواكب أحدث التقنيات ونقدم حلولًا مبتكرة تسبق المتطلبات وتلبي المستقبل.',
      'val.3.t': 'الشفافية',
      'val.3.d': 'نعمل بشفافية كاملة مع عملائنا في كل مرحلة من مراحل المشروع.',
      'val.4.t': 'الالتزام',
      'val.4.d': 'نلتزم بوعودنا من حيث الجودة والمواعيد والنتائج المطلوبة دائمًا.',
      'team.title': 'فريق العمل',
      'team.desc': 'كفاءات مصرية شابة تقف خلف كل نجاح نحققه.',
      'team.1.n': 'م. عمر حسن',
      'team.1.r': 'المؤسس والرئيس التنفيذي',
      'team.2.n': 'م. ليلى أحمد',
      'team.2.r': 'مديرة التكنولوجيا',
      'team.3.n': 'م. كريم سعيد',
      'team.3.r': 'مدير التطوير',
      'team.4.n': 'م. نورا فؤاد',
      'team.4.r': 'مصممة واجهات المستخدم',
      'services.page.title': 'خدماتنا',
      'services.page.desc': 'نقدم مجموعة شاملة من الخدمات البرمجية التي تغطي كل احتياجات شركتك الرقمية.',
      'svc.page.web.h2': 'تطوير مواقع الويب والتطبيقات',
      'svc.page.web.p': 'نصمم ونطور مواقع ويب وتطبيقات ويب متقدمة تجمع بين الجمال في التصميم والقوة في الأداء. نستخدم أحدث التقنيات مثل React وNext.js وNode.js لضمان سرعة وموثوقية عالية.',
      'svc.page.web.f1': 'مواقع متجاوبة بجميع الأجهزة',
      'svc.page.web.f2': 'تحسين محركات البحث SEO',
      'svc.page.web.f3': 'لوحات تحكم مخصصة',
      'svc.page.web.f4': 'ربط APIs وخدمات خارجية',
      'svc.page.mobile.h2': 'تطبيقات الجوال',
      'svc.page.mobile.p': 'نطور تطبيقات جوال احترافية لأنظمة Android وiOS باستخدام Flutter وReact Native، مما يوفر تطويرًا سريعًا وجودة عالية بأقل تكلفة ممكنة.',
      'svc.page.mobile.f1': 'تطبيقات أندرويد وiOS',
      'svc.page.mobile.f2': 'تصميم UX/UI احترافي',
      'svc.page.mobile.f3': 'إشعارات فورية ودفع إلكتروني',
      'svc.page.mobile.f4': 'نشر على المتاجر الرسمية',
      'svc.page.erp.h2': 'أنظمة إدارة الأعمال',
      'svc.page.erp.p': 'نبني أنظمة ERP وCRM مخصصة تناسب احتياجات شركتك تحديدًا، تُنظّم العمليات وتزيد الإنتاجية وتمنحك رؤية كاملة لأعمالك في الوقت الفعلي.',
      'svc.page.erp.f1': 'أنظمة ERP متكاملة',
      'svc.page.erp.f2': 'أنظمة CRM لإدارة العملاء',
      'svc.page.erp.f3': 'تقارير ولوحات معلومات',
      'svc.page.erp.f4': 'أتمتة العمليات',
      'svc.page.cloud.h2': 'الحوسبة السحابية',
      'svc.page.cloud.p': 'نساعد شركتك على الانتقال إلى السحابة وتوسعة بنى تحتية بطرق آمنة وفعالة من حيث التكلفة، مع ضمان أعلى مستويات الأمان والتوافر.',
      'svc.page.cloud.f1': 'استضافة AWS وGoogle Cloud',
      'svc.page.cloud.f2': 'أتمتة النشر المستمر CI/CD',
      'svc.page.cloud.f3': 'مراقبة أمنية على مدار الساعة',
      'svc.page.cloud.f4': 'تحسين التكلفة والأداء',
      'svc.page.ai.h2': 'الذكاء الاصطناعي',
      'svc.page.ai.p': 'نوفر حلول ذكاء اصطناعي متقدمة تشمل تعلم الآلة ومعالجة اللغة الطبيعية ورؤية الحاسوب، لتحويل بياناتك إلى قرارات ذكية وأتمتة ذكية.',
      'svc.page.ai.f1': 'روبوتات محادثة ذكية',
      'svc.page.ai.f2': 'تحليل البيانات والتوقعات',
      'svc.page.ai.f3': 'أتمتة العمليات بالذكاء',
      'svc.page.ai.f4': 'أنظمة توصية متقدمة',
      'svc.page.ecom.h2': 'التجارة الإلكترونية',
      'svc.page.ecom.p': 'نبني متاجر إلكترونية متكاملة وسريعة بأنظمة دفع وشحن ولوحات تحكم متقدمة، مع تحسين تجربة الشراء لزيادة معدلات التحويل والمبيعات.',
      'svc.page.ecom.f1': 'متاجر Shopify ومخصصة',
      'svc.page.ecom.f2': 'بوابات دفع محلية وعالمية',
      'svc.page.ecom.f3': 'ربط أنظمة الشحن',
      'svc.page.ecom.f4': 'تحسين معدلات التحويل',
      'portfolio.title': 'أعمالنا',
      'portfolio.desc': 'استعرض أحدث مشاريعنا ونماذج من الأعمال التي أنجزناها لعملائنا.',
      'contact.title': 'اتصل بنا',
      'contact.desc': 'يسعدنا تواصلك معنا. أرسل رسالتك وسنتواصل معك في أقرب وقت ممكن.',
      'contact.whatsapp': 'تواصل عبر واتساب',
      'contact.phone': 'اتصل بنا هاتفيًا',
      'contact.facebook': 'صفحتنا على فيسبوك',
      'contact.h2': 'لنتحدث عن مشروعك',
      'contact.lead': 'املأ النموذج وسيتواصل معك فريقنا خلال 24 ساعة لمناقشة احتياجاتك وإرسال عرض سعر مخصص.',
      'contact.f.name': 'الاسم الكامل',
      'contact.f.email': 'البريد الإلكتروني',
      'contact.f.phone': 'رقم الهاتف',
      'contact.f.company': 'اسم الشركة',
      'contact.f.service': 'الخدمة المطلوبة',
      'contact.f.select': 'اختر الخدمة',
      'contact.f.message': 'تفاصيل المشروع',
      'contact.f.placeholder': 'اكتب تفاصيل مشروعك هنا...',
      'contact.f.send': 'إرسال الرسالة',
      'contact.ok': 'تم إرسال رسالتك بنجاح! سنتواصل معك قريبًا.',
      'contact.err': 'من فضلك املأ جميع الحقول المطلوبة بشكل صحيح.',
      'contact.info.h2': 'معلومات التواصل',
      'contact.addr.t': 'العنوان',
      'contact.addr.d': 'القاهرة الجديدة، جمهورية مصر العربية',
      'contact.email.t': 'البريد الإلكتروني',
      'contact.phone.t': 'الهاتف',
      'contact.hours.t': 'ساعات العمل',
      'contact.hours.d': 'السبت - الخميس: 9 صباحًا - 6 مساءً',
      'contact.map.h2': 'موقعنا على الخريطة',
      'contact.map.a': 'خريطة موقع شركة بلوديسك في القاهرة الجديدة',
      'blog.title': 'المدونة',
      'blog.all': 'كل المقالات',
      'blog.search': 'بحث في المقالات',
      'blog.read': 'اقرأ المقال',
      'blog.read-also': 'اقرأ أيضًا',
      'blog.no-results': 'لا توجد نتائج',
      'blog.no-posts': 'لا توجد مقالات منشورة بعد',
      'blog.try-search': 'جرّب كلمات بحث أخرى.',
      'blog.follow': 'تابعنا قريبًا للاطلاع على أحدث مقالات BlueDesk.',
      'blog.results-for': 'نتائج البحث عن:',
      'blog.read-time': 'دقائق قراءة',
      'blog.tags': 'الوسوم:',
      'blog.share': 'شارك المقال',
      'blog.related': 'مقالات ذات صلة',
      'blog.trusted': 'محتوى تقني موثوق',
      'blog.aside': 'أفكار عملية تساعدك على تطوير أعمالك ومنتجاتك الرقمية.',
      'blog.contact': 'تواصل مع فريقنا',
      'brand': 'BlueDesk',
      'meta.desc.ar': 'شركة بلوديسك - شركة برمجيات مصرية متخصصة في تطوير الويب وتطبيقات الجوال وأنظمة الأعمال والسحابة والذكاء الاصطناعي والتجارة الإلكترونية. حلول برمجية متكاملة بجودة عالمية.',
      'meta.desc.about': 'تعرّف على شركة بلوديسك، شركة برمجيات مصرية رائدة في الحلول الرقمية المتكاملة. فريق من الخبراء وقيم تصنع الفرق.',
      'meta.desc.services': 'خدمات بلوديسك البرمجية: تطوير مواقع الويب، تطبيقات الجوال، أنظمة ERP وCRM، الحوسبة السحابية، الذكاء الاصطناعي والتجارة الإلكترونية.',
      'meta.desc.portfolio': 'استعرض أعمال شركة بلوديسك ومشاريعنا المنجزة في تطوير الويب والتطبيقات والأنظمة الذكية والتجارة الإلكترونية.',
      'meta.desc.contact': 'تواصل مع شركة بلوديسك للحصول على استشارة مجانية وعرض سعر لمشروعك البرمجي. فريقنا جاهز لخدمتك.'
    },
    en: {
      'nav.home': 'Home',
      'nav.about': 'About',
      'nav.services': 'Services',
      'nav.portfolio': 'Portfolio',
      'nav.contact': 'Contact',
      'nav.cta': 'Get a Quote',
      'skip.link': 'Skip to main content',
      'hero.badge': 'Egyptian Software Company · Full-Cycle Solutions',
      'hero.h1a': 'We Build Software',
      'hero.h1b': 'Solutions That',
      'hero.h1c': 'Drive Your',
      'hero.h1d': 'Business Forward',
      'hero.lead': 'BlueDesk is an Egyptian software company specializing in web development, mobile apps, business systems, cloud computing and AI. We turn your ideas into professional digital products.',
      'hero.cta1': 'Start Your Project',
      'hero.cta2': 'View Our Work',
      'hero.stat1': 'Projects Delivered',
      'hero.stat2': 'Happy Clients',
      'hero.stat3': 'Years Experience',
      'hero.stat4': 'Pro Developers',
      'hero.f1': 'Web Development',
      'hero.f2': 'Mobile Apps',
      'hero.f3': 'Cloud Solutions',
      'services.tag': 'Our Services',
      'services.title': 'Complete Software Solutions for Every Need',
      'services.desc': 'From idea to launch, we deliver comprehensive software services covering every stage of your digital transformation.',
      'svc1.t': 'Web Development',
      'svc1.d': 'Fast, responsive websites and web apps with modern design that attract customers and meet the highest quality standards.',
      'svc2.t': 'Mobile Apps',
      'svc2.d': 'High-performance Android and iOS apps with seamless UX that keeps your customers coming back.',
      'svc3.t': 'Business Systems',
      'svc3.d': 'Custom ERP and CRM systems that organize your operations and boost productivity and team efficiency.',
      'svc4.t': 'Cloud Computing',
      'svc4.d': 'Secure, reliable cloud hosting, deployment and scaling on AWS and Google Cloud.',
      'svc5.t': 'Artificial Intelligence',
      'svc5.d': 'Smart solutions using machine learning and NLP to automate decisions and analyze data.',
      'svc6.t': 'E-Commerce',
      'svc6.d': 'Complete online stores with payment, shipping and dashboards that grow your sales.',
      'svc.more': 'Details',
      'why.tag': 'Why BlueDesk',
      'why.title': 'A Tech Partner You Trust for Real Digital Success',
      'why.desc': 'We combine deep technical expertise with a thorough understanding of the Egyptian market to deliver practical solutions and measurable results.',
      'why.1.t': 'Deep Technical Expertise',
      'why.1.d': 'A team of professional developers experienced in the latest technologies and best practices.',
      'why.2.t': 'On-Time Delivery',
      'why.2.d': 'We stick to timelines and deliver projects on schedule without compromising quality.',
      'why.3.t': '24/7 Tech Support',
      'why.3.d': 'A support team available around the clock to keep your systems running without interruption.',
      'why.4.t': 'Competitive Pricing',
      'why.4.d': 'High-quality software solutions at fair prices suited to startups and enterprises alike.',
      'why.badge.n': '98%',
      'why.badge.t': 'Client Satisfaction',
      'work.tag': 'Our Work',
      'work.title': 'Projects We Are Proud Of',
      'work.desc': 'A selection of projects we have delivered for clients across industries.',
      'work.all': 'View All Work',
      'work.demo': 'Try the project',
      'work.details': 'View details',
      'work.back': 'Back to projects',
      'project.overview': 'About this project',
      'project.tech': 'Technologies used',
      'project.gallery': 'Project gallery',
      'project.notfound': 'This project does not exist or is no longer published.',
      'project.image.viewer': 'Project image viewer',
      'project.image.close': 'Close image',
      'project.image.prev': 'Previous image',
      'project.image.next': 'Next image',
      'branches.tag': 'Our Branches',
      'branches.title': 'Two Offices at Your Service: Qalyub & Dokki',
      'branches.desc': 'Visit our main headquarters or our second branch, or call us any time.',
      'branches.open_map': 'Open in Google Maps',
      'branches.call': 'Call Us',
      'work.p1.t': 'E-Commerce Platform',
      'work.p1.d': 'A complete online store with payment, shipping and an advanced admin dashboard.',
      'work.p1.tag': 'E-Commerce',
      'work.p2.t': 'Hospital Management System',
      'work.p2.d': 'An integrated system for appointments, medical records and inventory.',
      'work.p2.tag': 'Healthcare',
      'work.p3.t': 'Food Delivery App',
      'work.p3.d': 'A mobile delivery app with live tracking and a restaurant dashboard.',
      'work.p3.tag': 'Mobile App',
      'work.p4.t': 'E-Learning Platform',
      'work.p4.d': 'An online learning platform with live classes, recordings and interactive quizzes.',
      'work.p4.tag': 'E-Learning',
      'work.p5.t': 'Analytics Dashboard',
      'work.p5.d': 'A smart BI dashboard for real-time data analysis and decision making.',
      'work.p5.tag': 'AI & Data',
      'work.p6.t': 'Corporate Consulting Website',
      'work.p6.d': 'A professional multilingual website optimized for search engines with a CMS.',
      'work.p6.tag': 'Web Development',
      'portfolio.filter.all': 'All',
      'portfolio.filter.web': 'Web',
      'portfolio.filter.mobile': 'Apps',
      'portfolio.filter.systems': 'Systems',
      'portfolio.filter.ai': 'AI',
      'process.tag': 'Our Process',
      'process.title': 'How We Work With You Step by Step',
      'process.desc': 'A clear, organized process that ensures the best results for your project.',
      'pr.1.t': 'Discovery & Analysis',
      'pr.1.d': 'We understand your needs precisely and analyze the market and competitors to define project goals.',
      'pr.2.t': 'Design & Development',
      'pr.2.d': 'We design beautiful interfaces and build robust systems with the latest technologies.',
      'pr.3.t': 'Testing & Launch',
      'pr.3.d': 'We test every aspect thoroughly, then launch with top quality and minimal risk.',
      'pr.4.t': 'Support & Growth',
      'pr.4.d': 'We stay with you after launch to support, evolve and grow your product.',
      'test.tag': 'Testimonials',
      'test.title': 'What Our Clients Say',
      'test.desc': 'Real success stories from clients who trusted us and achieved exceptional results.',
      'test.1.q': 'The BlueDesk team built us an e-commerce platform that increased our sales by 300% in one year. Highly professional and fully on schedule.',
      'test.1.n': 'Ahmed El-Sharif',
      'test.1.r': 'CEO, Elite Store',
      'test.2.q': 'The AI solution they built automated our customer service and cut response time to under one second. A fantastic experience.',
      'test.2.n': 'Sara Mohamed',
      'test.2.r': 'Digital Transformation Director, Future Bank',
      'test.3.q': 'From the first meeting to launch, the team was organized and professional. The ERP system changed how our company works entirely.',
      'test.3.n': 'Khalid Al-Otaibi',
      'test.3.r': 'COO, Al-Fajr Group',
      'cta.title': 'Ready to Start Your Digital Project?',
      'cta.desc': 'Contact us today and get a free consultation and a custom quote for your project.',
      'cta.btn': 'Contact Us Now',
      'footer.desc': 'An Egyptian software company delivering full-cycle solutions across web development, mobile apps, business systems, cloud and AI.',
      'footer.links': 'Quick Links',
      'footer.services': 'Services',
      'footer.contact': 'Contact Info',
      'footer.rights': 'All rights reserved',
      'footer.made': 'Made with love in Egypt',
      'about.title': 'About Us',
      'about.desc': 'Learn about BlueDesk, our success story and the values we live by every day.',
      'about.h2': 'Your Trusted Tech Partner on the Digital Transformation Journey',
      'about.p1': 'BlueDesk is an Egyptian software company specializing in full-cycle software solutions. Founded to empower businesses through high-quality digital products.',
      'about.p2': 'We believe technology should be accessible to everyone, so we deliver software solutions at fair prices with world-class quality, backed by Egypt’s best developers, designers and engineers.',
      'about.ck1': '150+ software projects delivered',
      'about.ck2': 'A team of 40+ professional developers & engineers',
      'about.ck3': '24/7 technical support',
      'about.ck4': 'Competitive pricing, world-class quality',
      'values.title': 'Our Values',
      'values.tag': 'Our Values',
      'values.desc': 'The principles we live by and commit to in every project we deliver.',
      'val.1.t': 'Quality',
      'val.1.d': 'We never settle for less than the best. Every line of code follows strict quality standards.',
      'val.2.t': 'Innovation',
      'val.2.d': 'We track the latest technologies and deliver innovative solutions built for the future.',
      'val.3.t': 'Transparency',
      'val.3.d': 'We work with full transparency with our clients at every stage of the project.',
      'val.4.t': 'Commitment',
      'val.4.d': 'We keep our promises on quality, timelines and results — always.',
      'team.title': 'Our Team',
      'team.desc': 'Young Egyptian talents behind every success we achieve.',
      'team.1.n': 'Eng. Omar Hassan',
      'team.1.r': 'Founder & CEO',
      'team.2.n': 'Eng. Laila Ahmed',
      'team.2.r': 'Chief Technology Officer',
      'team.3.n': 'Eng. Karim Saeed',
      'team.3.r': 'Head of Development',
      'team.4.n': 'Eng. Noura Fouad',
      'team.4.r': 'UI/UX Designer',
      'services.page.title': 'Our Services',
      'services.page.desc': 'A comprehensive range of software services covering every digital need of your business.',
      'svc.page.web.h2': 'Web & Web-App Development',
      'svc.page.web.p': 'We design and build advanced websites and web apps combining beautiful design with powerful performance. We use modern stacks like React, Next.js and Node.js for speed and reliability.',
      'svc.page.web.f1': 'Fully responsive on all devices',
      'svc.page.web.f2': 'Search engine optimization (SEO)',
      'svc.page.web.f3': 'Custom admin dashboards',
      'svc.page.web.f4': 'API & third-party integrations',
      'svc.page.mobile.h2': 'Mobile Applications',
      'svc.page.mobile.p': 'We build professional mobile apps for Android and iOS using Flutter and React Native, delivering speed and quality at the best cost.',
      'svc.page.mobile.f1': 'Android & iOS apps',
      'svc.page.mobile.f2': 'Professional UX/UI design',
      'svc.page.mobile.f3': 'Push notifications & mobile payment',
      'svc.page.mobile.f4': 'Publishing on official stores',
      'svc.page.erp.h2': 'Business Management Systems',
      'svc.page.erp.p': 'We build custom ERP and CRM systems tailored to your exact needs, organizing operations, boosting productivity and giving you real-time visibility.',
      'svc.page.erp.f1': 'Integrated ERP systems',
      'svc.page.erp.f2': 'CRM for customer management',
      'svc.page.erp.f3': 'Reports & dashboards',
      'svc.page.erp.f4': 'Process automation',
      'svc.page.cloud.h2': 'Cloud Computing',
      'svc.page.cloud.p': 'We help your business move to the cloud and scale infrastructure securely and cost-effectively, with top-tier availability and security.',
      'svc.page.cloud.f1': 'AWS & Google Cloud hosting',
      'svc.page.cloud.f2': 'CI/CD automated deployment',
      'svc.page.cloud.f3': '24/7 security monitoring',
      'svc.page.cloud.f4': 'Cost & performance tuning',
      'svc.page.ai.h2': 'Artificial Intelligence',
      'svc.page.ai.p': 'We deliver advanced AI solutions including machine learning, NLP and computer vision — turning your data into smart decisions and automation.',
      'svc.page.ai.f1': 'Intelligent chatbots',
      'svc.page.ai.f2': 'Data analytics & predictions',
      'svc.page.ai.f3': 'AI-powered automation',
      'svc.page.ai.f4': 'Advanced recommendation systems',
      'svc.page.ecom.h2': 'E-Commerce',
      'svc.page.ecom.p': 'We build fast, complete online stores with payment, shipping and advanced dashboards — optimized to increase conversion and sales.',
      'svc.page.ecom.f1': 'Shopify & custom stores',
      'svc.page.ecom.f2': 'Local & global payment gateways',
      'svc.page.ecom.f3': 'Shipping carrier integrations',
      'svc.page.ecom.f4': 'Conversion rate optimization',
      'portfolio.title': 'Our Work',
      'portfolio.desc': 'Explore our latest projects and a selection of work delivered for our clients.',
      'contact.title': 'Contact Us',
      'contact.desc': 'We would love to hear from you. Send us a message and we will get back to you shortly.',
      'contact.whatsapp': 'Contact us on WhatsApp',
      'contact.phone': 'Call us by phone',
      'contact.facebook': 'Visit us on Facebook',
      'contact.h2': "Let's Talk About Your Project",
      'contact.lead': 'Fill out the form and our team will reach you within 24 hours to discuss your needs and send a custom quote.',
      'contact.f.name': 'Full Name',
      'contact.f.email': 'Email Address',
      'contact.f.phone': 'Phone Number',
      'contact.f.company': 'Company Name',
      'contact.f.service': 'Service Needed',
      'contact.f.select': 'Select a service',
      'contact.f.message': 'Project Details',
      'contact.f.placeholder': 'Describe your project here...',
      'contact.f.send': 'Send Message',
      'contact.ok': 'Your message has been sent successfully! We will contact you soon.',
      'contact.err': 'Please fill in all required fields correctly.',
      'contact.info.h2': 'Contact Information',
      'contact.addr.t': 'Address',
      'contact.addr.d': 'New Cairo, Egypt',
      'contact.email.t': 'Email',
      'contact.phone.t': 'Phone',
      'contact.hours.t': 'Working Hours',
      'contact.hours.d': 'Sat - Thu: 9 AM - 6 PM',
      'contact.map.h2': 'Find Us on the Map',
      'contact.map.a': 'Map showing BlueDesk office location in New Cairo',
      'blog.title': 'Blog',
      'blog.all': 'All articles',
      'blog.search': 'Search articles',
      'blog.read': 'Read article',
      'blog.read-also': 'Read also',
      'blog.no-results': 'No results found',
      'blog.no-posts': 'No articles published yet',
      'blog.try-search': 'Try different search terms.',
      'blog.follow': 'Check back soon for the latest BlueDesk articles.',
      'blog.results-for': 'Search results for:',
      'blog.read-time': 'min read',
      'blog.tags': 'Tags:',
      'blog.share': 'Share this article',
      'blog.related': 'Related articles',
      'blog.trusted': 'Trusted technology insights',
      'blog.aside': 'Practical ideas to help you grow your business and digital products.',
      'blog.contact': 'Talk to our team',
      'brand': 'BlueDesk',
      'meta.desc.ar': 'BlueDesk — Egyptian software company specializing in web development, mobile apps, business systems, cloud computing, AI and e-commerce.',
      'meta.desc.about': 'About BlueDesk, a leading Egyptian software company delivering full-cycle digital solutions with expert teams and real values.',
      'meta.desc.services': 'BlueDesk software services: web development, mobile apps, ERP & CRM systems, cloud computing, AI and e-commerce.',
      'meta.desc.portfolio': 'Explore BlueDesk portfolio — projects in web development, mobile apps, smart systems and e-commerce.',
      'meta.desc.contact': 'Contact BlueDesk for a free consultation and a custom software project quote. Our team is ready to serve you.'
    }
  };

  const html = document.documentElement;
  const requestedLang = new URLSearchParams(window.location.search).get('lang');
  const routeLang = requestedLang === 'en' || requestedLang === 'ar' ? requestedLang : null;
  let lang = routeLang || (document.body && document.body.getAttribute('data-blog-language-route') === '1'
    ? document.body.getAttribute('data-default-lang')
    : null) || localStorage.getItem('bd-lang') || 'ar';

  function applyLang(l) {
    const languageChanged = html.lang !== l;
    lang = l;
    html.lang = l;
    html.dir = l === 'ar' ? 'rtl' : 'ltr';
    localStorage.setItem('bd-lang', l);

    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (I18N[l] && I18N[l][key] != null && el.textContent !== I18N[l][key]) el.textContent = I18N[l][key];
    });
    document.querySelectorAll('[data-i18n-attr]').forEach(el => {
      const pairs = el.getAttribute('data-i18n-attr').split(';');
      pairs.forEach(p => {
        const [attr, key] = p.split(':').map(s => s.trim());
        if (I18N[l] && I18N[l][key] != null) el.setAttribute(attr, I18N[l][key]);
      });
    });

    document.querySelectorAll('.lang-switch button').forEach(btn => {
      const active = btn.getAttribute('data-lang') === l;
      btn.classList.toggle('active', active);
      btn.setAttribute('aria-pressed', active);
    });

    /* DB-driven bilingual content */
    document.querySelectorAll('[data-ar][data-en]').forEach(el => {
      const v = el.getAttribute('data-' + l);
      if (v !== null && el.textContent !== v) el.textContent = v;
    });
    document.querySelectorAll('[data-alt-ar][data-alt-en]').forEach(el => {
      const v = el.getAttribute('data-alt-' + l);
      if (v !== null) el.setAttribute('alt', v);
    });
    document.querySelectorAll('[data-project-lightbox-trigger]').forEach(trigger => {
      const image = trigger.querySelector('img[alt]');
      if (image) trigger.setAttribute('aria-label', image.alt);
    });
    if (languageChanged) {
      document.querySelectorAll('[data-ar-html][data-en-html]').forEach(el => {
        const v = el.getAttribute('data-' + l + '-html');
        if (v !== null) el.innerHTML = v;
      });
    }
    document.querySelectorAll('[data-ph-ar][data-ph-en]').forEach(el => {
      const v = el.getAttribute('data-ph-' + l);
      if (v !== null) el.setAttribute('placeholder', v);
    });

    document.querySelectorAll('a[href]').forEach(link => {
      try {
        const target = new URL(link.href, window.location.href);
        if (target.origin === window.location.origin && (/\.php$/i.test(target.pathname) || /\/blog(?:\/|$)/i.test(target.pathname))) {
          if (l === 'en') target.searchParams.set('lang', 'en');
          else target.searchParams.delete('lang');
          link.href = target.toString();
        }
      } catch (_) { /* Ignore non-URL anchors. */ }
    });

    /* Titles & meta from PHP */
    const body = document.body;
    if (body) {
      const title = body.getAttribute('data-title-' + l);
      const desc = body.getAttribute('data-desc-' + l);
      if (title) {
        document.title = title;
        document.querySelectorAll('meta[property="og:title"], meta[name="twitter:title"]')
          .forEach(m => m.setAttribute('content', title));
      }
      if (desc) {
        document.querySelectorAll('meta[name="description"], meta[property="og:description"], meta[name="twitter:description"]')
          .forEach(m => m.setAttribute('content', desc));
      }
    }
  }

  document.querySelectorAll('.lang-switch button').forEach(btn => {
    btn.addEventListener('click', () => {
      const selected = btn.getAttribute('data-lang');
      const nextUrl = new URL(window.location.href);
      const currentRouteLang = nextUrl.searchParams.get('lang') === 'en' ? 'en' : 'ar';
      if (selected !== currentRouteLang) {
        if (selected === 'en') nextUrl.searchParams.set('lang', 'en');
        else nextUrl.searchParams.delete('lang');
        window.location.assign(nextUrl.toString());
        return;
      }
      applyLang(selected);
    });
  });

  const projectLightbox = document.querySelector('[data-project-lightbox-dialog]');
  if (projectLightbox) {
    const imageTriggers = Array.from(document.querySelectorAll('[data-project-lightbox-trigger]'));
    const lightboxImage = projectLightbox.querySelector('[data-project-lightbox-image]');
    const closeButton = projectLightbox.querySelector('[data-project-lightbox-close]');
    const previousButton = projectLightbox.querySelector('[data-project-lightbox-prev]');
    const nextButton = projectLightbox.querySelector('[data-project-lightbox-next]');
    const counter = projectLightbox.querySelector('[data-project-lightbox-counter]');
    let currentImageIndex = 0;
    let returnFocusTarget = null;

    const showProjectImage = index => {
      if (!imageTriggers.length || !lightboxImage) return;
      currentImageIndex = (index + imageTriggers.length) % imageTriggers.length;
      const trigger = imageTriggers[currentImageIndex];
      const thumbnail = trigger.querySelector('img');
      lightboxImage.src = trigger.getAttribute('data-image-src') || (thumbnail && thumbnail.currentSrc) || '';
      lightboxImage.alt = thumbnail ? thumbnail.alt : '';
      if (counter) counter.textContent = (currentImageIndex + 1) + ' / ' + imageTriggers.length;
    };

    imageTriggers.forEach((trigger, index) => {
      trigger.addEventListener('click', () => {
        returnFocusTarget = trigger;
        showProjectImage(index);
        if (imageTriggers.length < 2) {
          if (previousButton) previousButton.hidden = true;
          if (nextButton) nextButton.hidden = true;
        }
        if (typeof projectLightbox.showModal === 'function') projectLightbox.showModal();
        else projectLightbox.setAttribute('open', '');
        if (closeButton) closeButton.focus();
      });
    });

    if (previousButton) previousButton.addEventListener('click', () => showProjectImage(currentImageIndex - 1));
    if (nextButton) nextButton.addEventListener('click', () => showProjectImage(currentImageIndex + 1));
    if (closeButton) closeButton.addEventListener('click', () => projectLightbox.close());
    projectLightbox.addEventListener('click', event => {
      if (event.target === projectLightbox) projectLightbox.close();
    });
    projectLightbox.addEventListener('keydown', event => {
      if (imageTriggers.length < 2) return;
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        showProjectImage(currentImageIndex + 1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        showProjectImage(currentImageIndex - 1);
      }
    });
    projectLightbox.addEventListener('close', () => {
      if (lightboxImage) lightboxImage.removeAttribute('src');
      if (returnFocusTarget) returnFocusTarget.focus();
    });
  }

  document.querySelectorAll('[data-editor-target]').forEach(button => {
    button.addEventListener('click', () => {
      const editor = document.getElementById(button.getAttribute('data-editor-target'));
      if (!editor) return;
      const start = editor.selectionStart;
      const end = editor.selectionEnd;
      const selected = editor.value.slice(start, end);
      let replacement;
      if (button.hasAttribute('data-list')) {
        const lines = (selected || button.getAttribute('data-placeholder') || 'Item').split(/\r?\n/);
        const escaped = lines.map(line => line.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'));
        replacement = '<ul>\n' + escaped.map(line => '<li>' + line + '</li>').join('\n') + '\n</ul>';
      } else {
        const content = selected || button.getAttribute('data-placeholder') || 'Text';
        replacement = (button.getAttribute('data-wrap-before') || '') + content + (button.getAttribute('data-wrap-after') || '');
      }
      editor.setRangeText(replacement, start, end, 'select');
      editor.focus();
    });
  });

  /* ---------- Navbar ---------- */
  const navbar = document.querySelector('.navbar');
  const toTop = document.querySelector('.to-top');
  const onScroll = () => {
    if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 10);
    if (toTop) toTop.classList.toggle('show', window.scrollY > 500);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  if (toTop) {
    toTop.addEventListener('click', () => {
      const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });
  }

  const hamburger = document.querySelector('.hamburger');
  const navLinks = document.querySelector('.nav-links');
  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      const open = navLinks.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', open);
    });
    navLinks.querySelectorAll('a').forEach(a =>
      a.addEventListener('click', () => {
        navLinks.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      })
    );
  }

  /* ---------- Reveal on scroll ---------- */
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));

  /* ---------- Counters ---------- */
  const counterIO = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const el = e.target;
      const target = parseInt(el.getAttribute('data-count'), 10);
      const suffix = el.getAttribute('data-suffix') || '';
      let cur = 0;
      const step = Math.max(1, Math.round(target / 60));
      const tick = () => {
        cur = Math.min(target, cur + step);
        el.textContent = cur + suffix;
        if (cur < target) requestAnimationFrame(tick);
      };
      tick();
      counterIO.unobserve(el);
    });
  }, { threshold: 0.4 });
  document.querySelectorAll('[data-count]').forEach(el => counterIO.observe(el));

  /* ---------- Contact form: client-side validation only; submit handled by PHP ---------- */
  const form = document.getElementById('contactForm');
  if (form) {
    form.addEventListener('submit', e => {
      const msg = form.querySelector('.form-msg');
      const name = form.querySelector('[name="name"]');
      const email = form.querySelector('[name="email"]');
      const message = form.querySelector('[name="message"]');
      const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
      if (!name.value.trim() || !emailOk || !message.value.trim()) {
        e.preventDefault();
        msg.className = 'form-msg err';
        msg.textContent = I18N[lang]['contact.err'];
        msg.focus && msg.focus();
      }
    });
  }

  /* ---------- Footer year ---------- */
  const yEl = document.getElementById('year');
  if (yEl) yEl.textContent = new Date().getFullYear();

  /* ---------- Init ---------- */
  applyLang(lang);
})();
