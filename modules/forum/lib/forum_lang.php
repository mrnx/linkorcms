<?php

# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# Строковые константы

$forum_lang = array();

# Основное
$forum_lang['site_slas'] = ' /  ';
$forum_lang['guest'] = 'Гость';
$forum_lang['forum'] = 'Форум';
$forum_lang['add'] =	'Добавить';
$forum_lang['delete'] = 'Удалить';
$forum_lang['deleted'] = 'Удалил';
$forum_lang['error'] = 'Ошибка';
$forum_lang['back'] = 'Назад';
$forum_lang['save'] = 'Сохранить';
$forum_lang['quick_transition'] = 'Быстрый переход';
$forum_lang['select_category'] = 'Выбор раздела.';
$forum_lang['execute'] = 'Выполнить';
$forum_lang['executed'] = 'Выполнено';
$forum_lang['all_rang'] = 'Все ранги';
$forum_lang['online'] = 'смотрят';
$forum_lang['reason'] = 'Причина';
$forum_lang['restore'] = 'Восстановить';
$forum_lang['first_page_forum'] = 'Главная страница форума';
$forum_lang['added'] = 'Добавлено: ' ;
$forum_lang['user_online'] = 'Сейчас на сайте.';
$forum_lang['author_topics'] = 'Авторов тем: ';
$forum_lang['active_author_topics'] = 'Активные авторы тем: ';
$forum_lang['subscription'] = 'Подписка';
$forum_lang['current_online'] = 'Здесь присутствуют ';
$forum_lang['current_category'] = 'В этом разделе ';
$forum_lang['all_online'] = 'Кто на форуме ';
$forum_lang['statistics'] = 'Статистика форума';
$forum_lang['statistics_cat'] = 'Статистика раздела';
$forum_lang['topics'] = 'Тем: ';
$forum_lang['reply'] = 'Ответов: ';
$forum_lang['hits'] = 'Просмотров: ';
$forum_lang['pages'] = 'Страницы: ';
$forum_lang['usertopics'] = 'Темы созданные : ';
$forum_lang['allusertopics'] = 'Все темы автора.';
$forum_lang['mark_all_read'] = 'Отметить все как прочитанные';
$forum_lang['viewnoread'] = 'Показать все не прочитанные темы';
$forum_lang['viewlasttopics'] = 'Показать последние созданные темы';
$forum_lang['viewnoreadtitle'] = 'Не прочитанные темы';
$forum_lang['return_read'] = 'Вернуться к просмотру';

#Подписка
$forum_lang['hello'] = "Здравствуйте.\r\n";
$forum_lang['add_message'] = " разместил новое сообщение в теме \"";
$forum_lang['last_subscription'] = "\" на которую Вы ранее подписались.\r\n";
$forum_lang['view_message'] = "Посмотреть сообщение можно по этой ссылке.\r\n";
$forum_lang['delete_subscription'] = 'Для отписки от получения уведомлений в данной теме пройдите по следующей ссылке'."\r\n";
$forum_lang['auto_message'] = 'Сообщение создано автоматически - отвечать на него не надо'."\r\n";
$forum_lang['robot'] = 'робот с сервера '.$config['general']['site_url'];
$forum_lang['new_message'] = 'Оповещение о новом сообщении в теме ';


$forum_lang['viewlasttopics24'] = 'за последние 24 часа';
$forum_lang['viewlasttopics2'] = 'за 2 дня';
$forum_lang['viewlasttopics3'] = 'за 3 дня';
$forum_lang['viewlasttopics4'] = 'за 4 дня';
$forum_lang['viewlasttopics5'] = 'за 5 дней';
$forum_lang['viewlasttopics6'] = 'за 6 дней';
$forum_lang['viewlasttopics7'] = 'за 7 дней';
$forum_lang['viewlasttopics14'] = 'за 2 недели';
$forum_lang['viewlasttopics21'] = 'за 3 недели';
$forum_lang['viewlasttopics30'] = 'за 1 месяц';
$forum_lang['viewlasttopics60'] = 'за 2 месяца';
$forum_lang['viewlasttopics90'] = 'за 3 месяца';
$forum_lang['viewlasttopics120'] = 'за 4 месяца';
$forum_lang['viewlasttopics150'] = 'за 5 месяцев';
$forum_lang['viewlasttopics180'] = 'за 6 месяцев';
$forum_lang['viewlasttopics365'] = 'за 1 год';

#Редактирование
$forum_lang['moderation_messages'] ='Модерация сообщений ';
$forum_lang['confirm'] = 'Потверждение действия.';
$forum_lang['edit_post'] = 'Редактирование сообщения';
$forum_lang['save_edit'] = 'Сохранить изменения и вернуться к теме';
$forum_lang['add_post'] ='Добавить сообщение';
$forum_lang['delete_post'] = $forum_lang['delete'].' сообщение';
$forum_lang['delete_posts'] = $forum_lang['delete'].' сообщения';
$forum_lang['merge_posts'] = 'Объединить сообщения';
$forum_lang['delete_topic'] = $forum_lang['delete'].' тему';
$forum_lang['merge_dest_topic'] = 'Тема назначения при объединении ';
$forum_lang['delete_topics'] = $forum_lang['delete'].' темы';
$forum_lang['open_topics'] = 'Открыть темы';
$forum_lang['close_topics'] = 'Закрыть темы';
$forum_lang['important_topics'] = 'Установить для темы статус "Важная"';
$forum_lang['remove_important_topics'] = 'Снять с темы статус "Важная"';
$forum_lang['move_topics'] = 'Переместить темы';
$forum_lang['merge_topics']  = 'Объединить темы';

#Предупреждения - Информирование
$forum_lang['warning'] = 'Внимание.';
$forum_lang['close_for_discussion'] = '<br><span style="color: #FF0066;">(Закрыто для обсуждения)</span>';
$forum_lang['topic_close_for_discussion'] = '<span style="color: #FF0066;">(Закрыто для обсуждения)</span>';
$forum_lang['on_for_discussion'] = '<span style="color: #339900;">Открыто</span>';
$forum_lang['close_for_discussion_admin'] = '<span style="color: #FF0066;">Закрыто</span>';
$forum_lang['close_for_discussion_admin_parent'] = '<span style="color: #FF0066;">Закрыто (закрыта категория)</span>';
$forum_lang['category_locked'] = ' : Категория закрыта';
$forum_lang['topic_close'] = '<br> Тема закрыта';
$forum_lang['it_is_ important'] ='Важно: ';
$forum_lang['no_category'] = '<center>Категорий пока нет.</center>';
$forum_lang['topic_basket_current_post'] = 'Тема этого сообщения - в очереди на удалении';
$forum_lang['topic_basket'] = 'Тема на удалении';
$forum_lang['topic_basket_current'] = '<center> На данный момент тема находится на удалении , но её ещё можно востановить.<br>Если она для Вас важна - свяжитесь с администрацией.';
$forum_lang['topic_basket_red'] ='<span style="color: #FF0000;">Тема на удалении</span>';
$forum_lang['no_topic_basket_edit'] = ' Запрещено изменять удаляемые темы.';
$forum_lang['topic_basket_post'] ='На данный момент тема этого сообщения находится на удалении , но её ещё можно востановить.</BR>'.$forum_lang['no_topic_basket_edit'];
$forum_lang['post_basket'] = 'Сообщение в очереди на удалении';
$forum_lang['post_basket_no_edit'] = 'На данный момент сообщение находится на удалении , но его ещё можно востановить.</BR>Запрещено изменять удаляемые сообщения.Если оно для Вас важно - свяжитесь с администрацией.';
$forum_lang['create_new_topics'] = 'Вы можете создавать новые темы.';
$forum_lang['no_create_new_topics'] = 'Вы не можете создавать новые темы.';
$forum_lang['create_new_topics_admin'] = 'Cоздавать новые темы в этом разделе может только администрация.';

$forum_lang['create_new_message_in_topics'] = 'Вы можете отвечать в темах.';
$forum_lang['no_create_new_message_in_topics'] = 'Вы не можете отвечать в темах.';
$forum_lang['no_create_new_message_current_topic'] = '<B>Вы не можете отвечать в этой теме</B>.';
$forum_lang['no_create_new_message_current_topic_add'] = '<B>Вы не можете добавить сообщение в этой теме</B>.';

$forum_lang['for_auth_user'] = 'Закрытая информация,только для зарегистрированных пользователей !';

$forum_lang['basket_delete_forever'] = 'Будет удалено на всегда  :';
$forum_lang['basket_removed_in_basket_message'] = '<center><span style="color: #FF0000;"><b>Удалено в корзину.</b> <br>Видят только администраторы.</span></center>';
$forum_lang['basket_see'] = 'Посмотреть удаляемое';
$forum_lang['basket_removed_in_basket_message_smile'] = '<center><span style="color: #FF0000; font-weight: bold;">Удалено в корзину.</span></center>';
$forum_lang['subscription_add'] = 'Подписка успешно добавлена!';
$forum_lang['subscription_delete']= 'Подписка успешно удалена.';

$forum_lang['lasttopicstitle'] = "Последние созданные темы";

#Ошибки
$forum_lang['error_access_category'] = 'Доступ в этот раздел ограничен.';
$forum_lang['error_auth'] = $forum_lang['error'].', необходимо авторизироваться.';
$forum_lang['error_no_forum'] = 'Форум не найден.';
$forum_lang['error_no_right_edit']	 = 'У вас недостаточно прав для редактирования';
$forum_lang['error_no_right_comment_edit'] =$forum_lang['error_no_right_edit'].' этого комментария.';
$forum_lang['error_comment_add'] = 'Ваше сообщение не добавлено по следующим причинам:';
$forum_lang['error_no_data'] = 'Данные не инициализированы.';
$forum_lang['error_no_reg_add'] = 'Чтобы оставлять сообщения на форуме необходимо зарегистрироваться.';
$forum_lang['error_no_message'] =  'Вы не ввели текст сообщения.';
$forum_lang['error_blocking'] =  '<span style="color: #FF0066">Вам запрещено добавлять комментарии в этом разделе сайта.<br><a href="index.php?name=blocking">Узнать за что запрещено.</a></span>';
$forum_lang['error_no_title_topic'] = 'Вы не ввели название темы.';
$forum_lang['error_data'] =  'Неверные данные.'	 ;
$forum_lang['error_no_forum'] = 'Укажите раздел форума а не категорию';
$forum_lang['error_no_topics'] = 'Не указаны темы.';
$forum_lang['error_no_messages'] = 'Не указаны сообщения.';
$forum_lang['error_subscription_exists'] = 'Вы уже подписаны на уведомления в этой теме';
$forum_lang['error_file_exists'] = 'Не найден файл ';


$forum_lang['lasttopics'] = 'Последние созданные темы';
$forum_lang['za'] = ' за ';
$forum_lang['last'] = 'последние ';
$forum_lang['day'][1] ='день';
$forum_lang['day'][2] ='дня';
$forum_lang['day'][3] ='дня';
$forum_lang['day'][4] ='дня';
$forum_lang['day'][5] ='дней';
$forum_lang['day'][6] ='дней';
$forum_lang['day'][7] ='дней';
$forum_lang['day'][8] ='дней';
$forum_lang['day'][9] ='дней';
$forum_lang['day'][0] ='дней';
