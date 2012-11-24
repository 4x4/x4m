   function  translit(word,ignorelowercase)
   {
        
       if(!ignorelowercase)
       {
           word=word.toLowerCase();
       }
       
       var en_lit = ["a", "b", "v", "g", "d", "e", "jo", "zh", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "kh", "ts", "ch", "sh", "sc", '', "y", "", "e", "ju", "ja", "w", "4",
       
   "A", "B", "V", "G", "D", "E", "JO", "ZH", "Z", "I", "J", "K", "I", "M", "N", "O", "P", "R", "S", "T", "U", "F", "KH", "TS", "CH", "SH", "SC", '', "Y", "", "E", "JU", "JA", "W", "4"
       
       ];
           var ru_lit = ["а", "б", "в", "г", "д", "е", "ё", "ж", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ь", "ы", "ъ", "э", "ю", "я", "в", "ч",
           
           "А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ь", "Ы", "Ъ", "Э", "Ю", "Я", "В", "Ч"
           ];    
        
       
    
        var z = '';
        v = word.gsub(/[^\f\n\r\t\v\u00A0\u2028\u2029\,]/, function(match){ key=ru_lit.indexOf(match[0]); if(key>-1){z+=en_lit[key];}else{if(!match[0].blank())z+=match[0];} });
        return z;
   
   
   }
   
_lang_xoad={}
_lang_xoad['invalid_response']='Неверный ответ сервера';
_lang_xoad['session_time_expired']='Время сессии истекло, обновите страницу';
_lang_xoad['empty_response']='Возвращен пустой результат';

_lang_common={}
_lang_common['info']='Информация';
_lang_common['error']='Ошибка';     
_lang_common['copy']='Копировать';
_lang_common['paste']='Вставить';
_lang_common['refresh']='Обновить';
_lang_common['delete']='Удалить';
_lang_common['editing']='Редактирование';
_lang_common['edit']='Редактировать';
_lang_common['save_error']='Ошибка сохранения';
_lang_common['warning']='Внимание'; 
_lang_common['group_success_saved']='Группа успешно сохранена';
_lang_common['group_is_not_saved']='Группа не сохранена';
_lang_common['add_group']='Добавить группу';
_lang_common['add_folder']='Добавить папку';
_lang_common['name']='Имя';
_lang_common['alias']='Алиас';
_lang_common['add']='Добавить';
_lang_common['save']='Сохранить';

_lang_common['set_permissions']='Установить права доступа';

_lang_common['select']='Выбрать';
_lang_common['change']='Изменить';            
_lang_common['error_on_server']='Ошибка на сервере';
_lang_common['export']='Экспорт';
_lang_common['manager']='Менеджер';
_lang_common['date']='Дата';
_lang_common['subject']='Название формы';
_lang_common['new_user']='Новый пользователь';
_lang_common['currency']='Валюта';
_lang_common['user']='Пользователь';
_lang_common['active']='Активен';
_lang_common['phone']='Телефон'
_lang_common['component_location']='Найти компонент на страницах';
_lang_common['properties_are_absent']='Свойства отсутствуют';
_lang_common['new']='Новые'; 
_lang_common['nothing_found']='Ничего не найдено';
_lang_common['options']='Настройки';
_lang_common['preservation_error']='Ошибка сохранения';
_lang_common['category_success_saved']='Категория успешно сохранена';
_lang_common['you_really_wish_to_remove_this_objects']='Вы действительно хотите удалить эти объекты?';
_lang_common['you_really_wish_to_copy_this_object']='Вы действительно хотите скопировать этот объект?';
_lang_common['you_really_wish_to_remove_this_object']='Вы действительно хотите удалить этот объект?';
_lang_common['comment']='Комментарий';
_lang_common['image']='Картинка';


_lang_common['add_category']='Добавить категорию';
_lang_common['it_is_changed']='Изменено';
_lang_common['sec']='сек';
_lang_common['modul']='Модуль';
_lang_common['restore']='Восстановить';
_lang_common['address']='Адрес';
_lang_common['successfully_added']='успешно добавлен';
_lang_common['status']='Статус';
_lang_common['user_with_such_name_already_exists']='Пользователь с таким именем уже существует';
_lang_common['user_success_saved']='Пользователь успешно сохранен';
_lang_common['group_users_success_saved']='Группа пользователей успешно сохранена';
_lang_common['name_group_properties']='Имя группы свойств';
_lang_common['options_not_found'] ='Свойства отсутствуют';
_lang_common['download_file'] ='Скачать файл';
_lang_common['boolean']='Логический тип';
_lang_common['January']='Январь';
_lang_common['February']='Февраль';
_lang_common['March']='Март';
_lang_common['April']='Апрель';
_lang_common['May']='Май';
_lang_common['June']='Июнь';
_lang_common['July']='Июль';
_lang_common['August']='Август';
_lang_common['September']='Сентябрь';
_lang_common['October']='Октябрь';
_lang_common['November']='Ноябрь';
_lang_common['December']='Декабрь';
  
_lang_common['view_comments']='Посмотреть комментарии';

 _lang_common['Mon']='Пн';
 _lang_common['Tue']='Вт';
 _lang_common['Wed']='Ср';
 _lang_common['Thu']='Чт';
 _lang_common['Fri']='Пт';
 _lang_common['Sat']='Cб';
 _lang_common['Sun']='Вск';  

_lang_common['you_are_assured_what_wish_to_remove_all']='Вы уверены в том что  хотите очистить все объекты?';
_lang_backup={}
_lang_backup['current_reserves']='Текущие резервы';
_lang_backup['make_reserve']='Сделать резерв';
_lang_backup['reserve_file']='Файл резерва';
_lang_backup['reserve_date']='Дата резерва';
_lang_backup['size']='Размер';
_lang_backup['restored']='Дамп успешно восстановлен';      
_lang_backup['restore']='Восстановить';
_lang_backup['saving_data']='Сохранение данных';
_lang_backup['dump_saved_file']='Дамп сохранен - файл';
_lang_backup['you_really_wish_to_remove_this_reserve']='Вы действительно хотите удалить этот резерв?';
_lang_backup['you_really_wish_to_restore']='Данные текущего будут замещены данными с резерва. Вы действительно хотите восстановить этот резерв?';
_lang_backup['table_name']='Название таблицы';
_lang_backup['data_length']='Размер';
_lang_backup['creation_time']='Время создания';
        


_lang_banners={}
_lang_banners['load_banner_file']='Загрузите файл баннера!';
_lang_banners['GIF-banners_do_not_support_format']='GIF-баннеры не поддерживают формат *.';
_lang_banners['load_format_file_gif_jpeg_png']='Загрузите файл формата *.gif, *.jpeg или *.png';
_lang_banners['FLASH-banners_do_not_support_format']='FLASH-баннеры не поддерживают формат *.';
_lang_banners['load_format_file_swf']='Загрузите файл формата *.swf';
_lang_banners['banner_success_saved']='Баннер успешно сохранен';
_lang_banners['banner_file_is_not_loaded']='Файл баннера не загружен!';
_lang_banners['enter_banner_html_code']='Введите html-код баннера!';
_lang_banners['select_format_file_swf']='Выберите файл формата *.swf';
_lang_banners['add_banner']='Добавить баннер';

_lang_catalog={}
_lang_catalog['such_element_already_is_present']='Такой элемент уже присутствует';
_lang_catalog['file']='Файл';
_lang_catalog['image']='Изображение';
_lang_catalog['field']='Поле';
_lang_catalog['selector']='Селектор';
_lang_catalog['text']='Текст';
_lang_catalog['currency']='Валюта';
_lang_catalog['folder_with_images']='Папка с изображениями';
_lang_catalog['object_catalog']='Объект каталога';
_lang_catalog['object_fuser']='Пользователь';
_lang_catalog['object_album']='Фотоальбом';
_lang_catalog['object_docs']='Документы';
_lang_catalog['object_faq']='Раздел вопрос-ответ';
_lang_catalog['object_sform']='Поисковая форма';
_lang_catalog['option_with_such_name_already_exists']='Опция с таким именем уже существует!';
_lang_catalog['date_dd_mm_gggg']='(Дата дд-мм-гггг):';
_lang_catalog['main_group']='Основная группа';
_lang_catalog['property_with_such_name_already_exists']='Свойство с таким именем уже существует!';
_lang_catalog['object_catalog_success_saved']='Объект каталога успешно сохранен';
_lang_catalog['you_really_wish_to_remove_this_group_properties']='Вы действительно хотите удалить эту группу свойств?';
_lang_catalog['group_properties_saved']='Группа свойств сохранена';
_lang_catalog['loading_is_finished']='Загрузка завершена';
_lang_catalog['group']='Имя группы свойств';
_lang_catalog['edit']='Ред.';
_lang_catalog['add_object']='Добавить объект';
_lang_catalog['delete']='Уд.';
_lang_catalog['warning_export_not_writable']='Папка media/export/ не доступна для записи';
_lang_catalog['export_data_not_found']='Данные для экспорта не найдены';
_lang_catalog['export_filewrite_fail']='Ошибка записи в файл экспорта';
_lang_catalog['object_with_such_name_already_exists']='Объект с таким именем уже существует';
_lang_catalog['catalog_objects']='Объекты каталога';
_lang_catalog['propert_set']='Тип объекта';
_lang_catalog['edit_sfrom']='Редактирование поискового поля';
_lang_catalog['search_form']='Поисковая форма';
_lang_catalog['property_set']='Группа свойств';
_lang_catalog['edit_property']='Редактировать свойство';     
_lang_catalog['ishop_currency']='Валюта интернет-магазин';

_lang_catalog['import_from_excel']='Импорт из EXCEL';
_lang_catalog['groups_properties']='Группы свойств';

_lang_catalog['group']='Группа';
_lang_catalog['edit']='Ред';
_lang_catalog['delete'] ='Уд';

_lang_catalog['search_forms']='Формы поиска';

_lang_content={}
_lang_content['article_success_saved']='Статья успешно сохранена';
_lang_content['add_article']='Добавить статью';
_lang_content['content_groups']='Группы статей';
_lang_content['templates']='Шаблон';

_lang_faq={}
_lang_faq['question_success_saved']='Вопрос успешно сохранён';
_lang_faq['you_are_assured_what_wish_to_remove_this_question']='Вы уверены, что хотите удалить этот вопрос?';
_lang_faq['add_question']='Добавить вопрос';
_lang_faq['new_questions']='Новые вопросы';
_lang_faq['section questions']='Вопросы раздела';
_lang_faq['question']='Вопрос';

_lang_forms={}
_lang_forms['form_success_saved']='Форма успешно сохранена';
_lang_forms['form_name'] = 'Название формы';
_lang_forms['read'] = 'Прочитано';
_lang_forms['messages_deleted'] = 'Сообщения удалены';
_lang_forms['messages_moved_to_archive'] = 'Сообщения перемещены в архив';
_lang_forms['add_form']='Добавить форму';
_lang_forms['archive']='Архив';
_lang_forms['in_archive']='В архив';
_lang_forms['form name']='Имя формы';
_lang_forms['it_is_received']='Получено';
_lang_forms['incoming_message']='Входящее сообщение';
_lang_forms['you_really_wish_to_put_in_archive_this_message'] = 'Вы уверены, что хотите поместить в архив это сообщение(я)';
_lang_forms['you_really_wish_to_remove_this_message'] = 'Вы уверены, что хотите удалить это сообщение(я)?';

_lang_fusers={}
_lang_fusers['options_saved']='Настройки сохранены';
_lang_fusers['new_user']='Новый пользователь';
_lang_fusers['login']='Логин';    
_lang_fusers['Name']='Имя';    
_lang_fusers['user_moved']='Пользователь перемещен';

_lang_gallery={}
_lang_gallery['photogallery_success_saved']='Фотогалерея успешно сохранена';
_lang_gallery['photo_success_saved']='Фото успешно сохранено';
_lang_gallery['editing_photo']='Редактирование фото';
_lang_gallery['image_is_not_added']='Изображение не добавлено';
_lang_gallery['add_photo']='Добавить фото';
_lang_gallery['add_photoalbum']='Добавить фотоальбом';
_lang_gallery['add_folder']='Добавить папку';
_lang_gallery['add_gallery']='Добавить галерею';
_lang_gallery['delete_photoalbum']='Удалить фотоальбом';
_lang_gallery['album_parameters']='Параметры альбома';
_lang_gallery['no_photo']='В галерее 0 фото';

_lang_ishop={}
_lang_ishop['orders']='Заказы';
_lang_ishop['payment_systems']='Платежные системы';
_lang_ishop['schemes_discounts']='Схемы скидок';
_lang_ishop['discount_for_the_given_category_is_already_appointed']='Скидка для данной категории уже назначена';
_lang_ishop['you_really_wish_to_remove_this_scheme']='Вы действительно хотите удалить эту схему?';
_lang_ishop['name_scheme_discounts']='Имя схемы скидок';
_lang_ishop['options_success_saved']='Настройки  успешно сохранены';
_lang_ishop['number_in_catalog']='Номер в каталоге';
_lang_ishop['goods_name']='Наименование товара';
_lang_ishop['price']='Цена';
_lang_ishop['add_sheme']='Добавить схему';
_lang_ishop['quantity']='Количество';
_lang_ishop['sum']='Сумма';
_lang_ishop['comments']='Комментарии';
_lang_ishop['you_really_wish_to_remove_this_order']='Вы действительно хотите удалить этот заказ?';
_lang_ishop['order_number']='Номер заказа';
_lang_ishop['customer']='Заказчик';
_lang_ishop['order_sum']='Сумма заказа';
_lang_ishop['status']='Статус';
_lang_ishop['it_is_confirmed']='Подтвержден';
_lang_ishop['it_is_paid']='Оплачен';
_lang_ishop['new_order']='Новый заказ';
_lang_ishop['currency_name']='Наименование валюты';
_lang_ishop['currency_alias']='Алиас валюты';
_lang_ishop['rate']='Курс';
_lang_ishop['currency_is_main']='Основная валюта';
_lang_ishop['add_currency']='Добавить валюту';
_lang_ishop['edit_currency']='Редактировать валюту';

_lang_ishop['are_you_sure_to_recount']='Вы уверены, что хотите пересчитать курсы валют относительно основного';


_lang_news={}
_lang_news['you_really_wish_to_remove_this_news']='Вы действительно хотите удалить эту новость?';
_lang_news['group_news']='Новости группы';
_lang_news['news']='Новость';
_lang_news['add_news']='Добавить новость';
_lang_news['rss_tunes']='Настройки RSS';
_lang_news['edit_category']='Редактировать категорию';
_lang_news['news_moved']='Новость успешна перенесена в категорию';

_lang_pages={}
_lang_pages['add_page']='Добавить страницу';
_lang_pages['add_link']='Добавить ссылку';
_lang_pages['set_access_rights']='Установить права доступа';
_lang_pages['menu']='Меню';
_lang_pages['site_properties_success_saved']='Настройки сайта успешно сохранены';
_lang_pages['page_success_saved']='Страница успешно сохранена';
_lang_pages['value_of_this_field_is_not_unique_enter_other_value']='Значение этого поля не уникально,введите другое значение';
_lang_pages['in_menu_should_be_at_least_one_ element']='В меню должен быть хотя бы один элемент';
_lang_pages['menu_list']='Список меню';
_lang_pages['menu_name']='Название меню';
_lang_pages['301_redirect']='301 редирект';
_lang_pages['you_are_assured_what_wish_to_remove_the_chosen_menu']='Вы уверены, что хотите удалить выбранное меню?';
_lang_pages['link_success_saved']='Ссылка успешно сохранена';
_lang_pages['rights_saved']='Права сохранены';
_lang_pages['page_name']='Название страницы';
_lang_pages['link']='Ссылка';
_lang_pages['no_display']='Неактив';
_lang_pages['module_editor']='Редактор модуля';
_lang_pages['show_on_site']='Показать на сайте';
_lang_pages['routes']='Роуты';

_lang_pages['source']='Источник'; 
_lang_pages['destination']='Назначение'; 

_lang_price={}
_lang_price['price_success_saved']='Прайс успешно сохранен';
_lang_price['delete_files_of_prices_from_a_server']='Удалять файлы прайсов с сервера?';
_lang_price['prices_are_removed_successfully']='Прайсы удалены успешно';
_lang_price['table_name']='Название таблицы';
_lang_price['wrong_format_file']='Неправильный формат файла!';
_lang_price['resolved_format_xls']='Разрешённый формат - *.xls';
_lang_price['standart']='Стандартная';
_lang_price['non_standard']='Нестандартная';
_lang_price['table_is_successfully_loaded']='Таблица успешно загружена';
_lang_price['loading_time']='Время загрузки';
_lang_price['number_added_updated_elements']='Число добавленных / обновлённых элементов';
_lang_price['you_are_assured_what_wish_to_remove_the_chosen_table']='Вы уверены, что хотите удалить выбранную таблицу?';
_lang_price['table_successfully_saved']="Таблица успешно сохранена";
_lang_price['add_price']='Добавить документ';



_lang_recycle={}
_lang_recycle['object_name']='Имя объекта';
_lang_recycle['removal_time']='Время удаления';
_lang_recycle['remove_from_basket']='удалить из корзины?';
_lang_recycle['recycle']='Корзина удаленных объектов';
_lang_recycle['restore']='Восстановить';


_lang_search={}
_lang_search['indexation']='Индексация';
_lang_search['current_indexes']='Текущие индексы';
_lang_search['indexes_are_empty']='Индексы пусты';
_lang_search['size_page']='размер страницы';
_lang_search['link']='Ссылка';
_lang_search['title']='Заголовок(title)';
_lang_search['body']='Текст';
_lang_search['size']='Размер';
_lang_search['status']='Статус';

_lang_subscribe={}
_lang_subscribe['at_first_choose_a_file']='Сначала выберите файл';
_lang_subscribe['dispatch_success_saved']='Рассылка успешно сохранена';
_lang_subscribe['dispatch_saved']='Рассылка сохранена';
_lang_subscribe['you_are_assured_what_wish_to_remove_the_chosen_dispatch']='Вы уверены, что хотите удалить выбранную рассылку?';
_lang_subscribe['dispatches_expecting_sending_are_not_present']='Рассылок, ожидающих отправки, нет';
_lang_subscribe['it_is_prepared']='Подготовлено';
_lang_subscribe['dispatch(es) for']='рассылка(ки) для';
_lang_subscribe['user(s)']='пользователей(я)';
_lang_subscribe['dispatch']='Рассылка';
_lang_subscribe['dispatch_is_finished']='Рассылка завершена';
_lang_subscribe['add_dispatch']='Добавить рассылку';
_lang_subscribe['list_subscribers']='Список подписчиков';
_lang_subscribe['begin_dispatch']='Начать рассылку';
_lang_subscribe['operations_over_subscribers']='Операции над подписчиками';
_lang_subscribe['select_dispatch']='Выберите рассылку!';
_lang_subscribe['user_already_exists']='Пользователь уже существует';
_lang_subscribe['changes_successfully_saved']='Изменения успешно сохранены';
_lang_subscribe['you_are_assured_what_wish_to_remove_user']='Вы уверены, что хотите удалить пользователя?';
_lang_subscribe['user_is_not_found']='Пользователь не найден';
_lang_subscribe['list_dispatches']='Список рассылок';
_lang_subscribe['email'] = 'Email';
_lang_subscribe['status'] = 'Статус';
_lang_subscribe['subscribe_deleted'] = 'Рассылка удалена';
_lang_subscribe['user_deleted'] = 'Пользователи удалены';
_lang_subscribe['sent']='Отправлено';
_lang_subscribe['waiting']='Отложено';
_lang_subscribe['incoming']='В ожидании отправки';

_lang_templates={}
_lang_templates['template_saved']='Шаблон сохранен';
_lang_templates['aliases_success_saved']='Алиасы успешно сохранены';


_lang_users={}
_lang_users['schemes_roles']='Схемы ролей';
_lang_users['you_really_wish_to_remove_this_role']='Вы действительно хотите удалить эту роль?';
_lang_users['name_schemes_roles']='Имя cхемы ролей';

_lang_votes={}
_lang_votes['in_voting_should_be_at_least_2_variants_answer']='В голосовании должно быть хотя бы 2 варианта ответа!';
_lang_votes['voting_successfully_saved']='Голосование успешно сохранено';
_lang_votes['form_success_saved']='Форма успешно сохранена';
_lang_votes['new_voting']='Новое голосование';
_lang_votes['add_voting']='Добавить голосование';


_lang_main={}
_lang_main['error_loading_module']='Ошибка загрузки модуля';
_lang_main['error_loading_template_module']='Ошибка загрузки шаблона модуля';
_lang_main['close']='Закрыть';
_lang_main['yes']='Да';
_lang_main['no']='Нет';
_lang_main['fusers']='Пользователи сайта';
_lang_main['pages']='Страницы';
_lang_main['ishop']='Интернет-магазин';
_lang_main['banners']='Баннеры';
_lang_main['catalog']='Каталог';
_lang_main['forms']='Конструктор форм';
_lang_main['templates']='Шаблоны';
_lang_main['news']='Новости';
_lang_main['faq']='Вопрос-ответ';
_lang_main['price']='Прайсы и документы';
_lang_main['content']='Статьи';
_lang_main['gallery']='Фотогалерея';
_lang_main['users']='Пользователи';
_lang_main['votes']='Голосование';
_lang_main['search']='Поиск по сайту';
_lang_main['subscribe']='Рассылка';
_lang_main['backup']='Резервирование данных';
_lang_main['comments']='Комментарии';

_lang_main['very_poorly']='очень слабо';
_lang_main['very_poorly_such_password_is_easy_for_guessing']='очень слабо, такой пароль легко отгадать';
_lang_main['poorly_protected']='слабо защищен';
_lang_main['average_complexity']='средней сложности';
_lang_main['really_difficult']='действительно сложный';
_lang_main['extreme_difficult']='запредельно сложный';
_lang_main['complexity_password']='Сложность пароля';

_lang_xlist={}
_lang_xlist['choose']='Выбор';  
_lang_xlist['cancel']='Отмена';  




_lang_validation={}
_lang_validation['required']='Данное поле обязательно к заполнению';
_lang_validation['validate-number']='В этом поле может быть только число';
_lang_validation['validate-digits']='В этом поле могут быть только цифры и точки';
_lang_validation['validate-alpha']='В этом поле можно В этом поле можно только буквы, цифры,cкобки,тире и пробел.';
_lang_validation['validate-alpha-ext']='В этом поле можно только буквы,cкобки и пробел и спец.символы.';
_lang_validation['validate-alphanum']='В этом поле можно использовать символы только из дипазона от (a-Z), пробелы и прочие символы недопустимы';
_lang_validation['validate-date']='Это поле может содержать только дату.';
_lang_validation['validate-email']='Введите правильный e-mail, например ivanov@mymail.ru';   
_lang_validation['validate-url']='Введите правильный URL.';   
_lang_validation['validate-date-au']= 'Используйте следующий формат даты dd/mm/yyyy';
_lang_validation['validate-password']='пароль введен неправильно(не менее 6 символов,не должен быть равен имени пользователя)';
_lang_validation['validate-password-again']='пароли не совпадают';
_lang_validation['validate-selection']='Это поле должно иметь значение';
_lang_validation['validate-one-required']='Выберите хотя бы одну опцию.';
_lang_validation['validate-float']='В этом поле могут быть только цифры и  значения с плавающей точкой';

_lang_matrix={}
_lang_matrix['upload']='Закачка';
_lang_matrix['security_error']='Ошибка безопасности';
_lang_matrix['waiting']='Ожидание';
_lang_matrix['ready']='Готово';
_lang_matrix['file_manager']='Файл-менеджер';
_lang_matrix['enter_folder_name']='Введите имя папки';   
_lang_matrix['cant_create_folder']='Невозможно создать папку. Измените права доступа.';
_lang_matrix['select_files_to_copy']='Выделите файлы для копирования';
_lang_matrix['this_mode_allows_folders_selected_only']='В данном режиме могут быть выбраны только папки!';

_lang_comments={}
_lang_comments['add_tread']='Добавить тред';
_lang_comments['new_comments']='Новые комментарии';
_lang_comments['tread_success_saved']='Тред успешно сохранен';
_lang_comments['comment_object']='Комментируемый объект';
_lang_comments['module']='Модуль';
_lang_comments['comments_list']='Список комментариев';
_lang_comments['UserName']='Имя пользователя';
_lang_comments['Header']='Заголовок';
_lang_comments['Message']='Комменарий';
_lang_comments['you_really_wish_to_remove_this_comment']='Вы уверенны, что хотите удалить данные комментарии';
_lang_fed={}


_lang_news['link_is_not_uniq']='Ссылка не уникальна';

/*calandar related*/

            _lang_common['Now']='Сейчас';
            _lang_common['Today']='Сегодня'; 
            _lang_common['Time']='Время';
            _lang_common['Exact_minutes']='Точно минут';
            _lang_common['Select_Date_and_Time']='Выбрать дату и время';
            _lang_common['Select_Time']='Выберите время';
            _lang_common['Open_calendar']='Открыть календарь';
