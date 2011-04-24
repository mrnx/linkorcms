<?php

# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# Строковые константы

unset($lang);

# Основное
$lang['site_slas'] = ' /  ';
$lang['guest'] = 'Гость';
$lang['forum'] = 'Форум';
$lang['add'] =	'Добавить';
$lang['delete'] = 'Удалить';
$lang['deleted'] = 'Удалил';
$lang['error'] = 'Ошибка';
$lang['back'] = 'Назад';
$lang['save'] = 'Сохранить';
$lang['quick_transition'] = 'Быстрый переход';
$lang['select_category'] = 'Выбор раздела.';
$lang['execute'] = 'Выполнить';
$lang['executed'] = 'Выполнено';
$lang['all_rang'] = 'Все ранги';
$lang['online'] = 'смотрят';
$lang['reason'] = 'Причина';
$lang['restore'] = 'Восстановить';
$lang['first_page_forum'] = 'Главная страница форума';
$lang['added'] = 'Добавлено: ' ;
$lang['user_online'] = 'Сейчас на сайте.';
$lang['author_topics'] = 'Авторов тем: ';
$lang['active_author_topics'] = 'Активные авторы тем: ';
$lang['subscription'] = 'Подписка';
$lang['current_online'] = 'Здесь присутствуют ';
$lang['current_category'] = 'В этом разделе ';
$lang['all_online'] = 'Кто на форуме ';
$lang['statistics'] = 'Статистика форума';
$lang['statistics_cat'] = 'Статистика раздела';
$lang['topics'] = 'Тем: ';
$lang['reply'] = 'Ответов: ';
$lang['hits'] = 'Просмотров: ';
$lang['pages'] = 'Страницы: ';
$lang['usertopics'] = 'Темы созданные : ';
$lang['allusertopics'] = 'Все темы автора.';
$lang['mark_all_read'] = 'Отметить все как прочитанные';
$lang['viewnoread'] = 'Показать все не прочитанные темы';
$lang['viewlasttopics'] = 'Показать последние созданные темы';
$lang['viewnoreadtitle'] = 'Не прочитанные темы';
$lang['return_read'] = 'Вернуться к просмотру';

#Подписка
$lang['hello'] = "Здравствуйте.\r\n"; 
$lang['add_message'] = " разместил новое сообщение в теме \"";
$lang['last_subscription'] = "\" на которую Вы ранее подписались.\r\n";
$lang['view_message'] = "Посмотреть сообщение можно по этой ссылке.\r\n"; 
$lang['delete_subscription'] = 'Для отписки от получения уведомлений в данной теме пройдите по следующей ссылке'."\r\n";
$lang['auto_message'] = 'Сообщение создано автоматически - отвечать на него не надо'."\r\n";
$lang['robot'] = 'робот с сервера '.$config['general']['site_url'];
$lang['new_message'] = 'Оповещение о новом сообщении в теме ';


$lang['viewlasttopics24'] = 'за последние 24 часа';
$lang['viewlasttopics2'] = 'за 2 дня';
$lang['viewlasttopics3'] = 'за 3 дня';
$lang['viewlasttopics4'] = 'за 4 дня';
$lang['viewlasttopics5'] = 'за 5 дней';
$lang['viewlasttopics6'] = 'за 6 дней';
$lang['viewlasttopics7'] = 'за 7 дней';
$lang['viewlasttopics14'] = 'за 2 недели';
$lang['viewlasttopics21'] = 'за 3 недели';
$lang['viewlasttopics30'] = 'за 1 месяц';
$lang['viewlasttopics60'] = 'за 2 месяца';
$lang['viewlasttopics90'] = 'за 3 месяца';
$lang['viewlasttopics120'] = 'за 4 месяца';
$lang['viewlasttopics150'] = 'за 5 месяцев';
$lang['viewlasttopics180'] = 'за 6 месяцев';
$lang['viewlasttopics365'] = 'за 1 год';

#Редактирование
$lang['moderation_messages'] ='Модерация сообщений ';
$lang['confirm'] = 'Потверждение действия.';
$lang['edit_post'] = 'Редактирование сообщения';
$lang['save_edit'] = 'Сохранить изменения и вернуться к теме';
$lang['add_post'] ='Добавить сообщение';
$lang['delete_post'] = $lang['delete'].' сообщение';
$lang['delete_posts'] = $lang['delete'].' сообщения';
$lang['merge_posts'] = 'Объединить сообщения';
$lang['delete_topic'] = $lang['delete'].' тему';
$lang['merge_dest_topic'] = 'Тема назначения при объединении ';
$lang['delete_topics'] = $lang['delete'].' темы';
$lang['open_topics'] = 'Открыть темы';
$lang['close_topics'] = 'Закрыть темы';
$lang['important_topics'] = 'Установить для темы статус "Важная"';
$lang['remove_important_topics'] = 'Снять с темы статус "Важная"';
$lang['move_topics'] = 'Переместить темы';
$lang['merge_topics']  = 'Объединить темы';

#Предупреждения - Информирование
$lang['warning'] = 'Внимание.';
$lang['close_for_discussion'] = '<BR><FONT COLOR="#FF0066"> (Закрыто для обсуждения) </FONT>';
$lang['topic_close_for_discussion'] = '&nbsp;<FONT COLOR="#FF0066"> (Закрыто для обсуждения) </FONT>'	;
$lang['on_for_discussion'] = '&nbsp;<FONT COLOR="#339900"> Вкл.</FONT>';
$lang['close_for_discussion_admin'] = '&nbsp;<FONT COLOR="#FF0066"> Закрыто </FONT>';
$lang['close_for_discussion_admin_parent'] = '&nbsp;<FONT COLOR="#FF0066">  Закрыто </FONT><FONT SIZE="1" ><BR>(закрыта категория)</FONT> ';
$lang['category_locked'] = ' : Категория закрыта';
$lang['topic_close'] = '<BR> Тема закрыта';
$lang['it_is_ important'] ='Важно: ';
$lang['no_category'] = '<center>Категорий пока нет.</center>';
$lang['topic_basket_current_post'] = 'Тема этого сообщения - в очереди на удалении';
$lang['topic_basket'] = 'Тема на удалении';
$lang['topic_basket_current'] = '<center> На данный момент тема находится на удалении , но её ещё можно востановить.</BR>Если она для Вас важна - свяжитесь с администрацией.';	
$lang['topic_basket_red'] ='<FONT COLOR="#FF0000">Тема на удалении</FONT>';
$lang['no_topic_basket_edit'] = ' Запрещено изменять удаляемые темы.';
$lang['topic_basket_post'] ='На данный момент тема этого сообщения находится на удалении , но её ещё можно востановить.</BR>'.$lang['no_topic_basket_edit'];
$lang['post_basket'] = 'Сообщение в очереди на удалении';
$lang['post_basket_no_edit'] = 'На данный момент сообщение находится на удалении , но его ещё можно востановить.</BR>Запрещено изменять удаляемые сообщения.Если оно для Вас важно - свяжитесь с администрацией.';
$lang['create_new_topics'] = 'Вы можете создавать новые темы.';
$lang['no_create_new_topics'] = 'Вы не можете создавать новые темы.';
$lang['create_new_topics_admin'] = 'Cоздавать новые темы в этом разделе может только администрация.';

$lang['create_new_message_in_topics'] = 'Вы можете отвечать в темах.';
$lang['no_create_new_message_in_topics'] = 'Вы не можете отвечать в темах.';
$lang['no_create_new_message_current_topic'] = '<B>Вы не можете отвечать в этой теме</B>.';
$lang['no_create_new_message_current_topic_add'] = '<B>Вы не можете добавить сообщение в этой теме</B>.';

$lang['for_auth_user'] = 'Закрытая информация,только для зарегистрированных пользователей !';

$lang['basket_delete_forever'] = 'Будет удалено на всегда  :';
$lang['basket_removed_in_basket_message'] = '<CENTER><FONT  COLOR="#FF0000"><B>Удалено в корзину.</B> <BR>Видят только администраторы.</FONT></CENTER>';
$lang['basket_see'] = 'Посмотреть удаляемое';
$lang['basket_removed_in_basket_message_smile'] = '<CENTER><FONT  COLOR="#FF0000"><B>Удалено в корзину.</B> </FONT></CENTER>';
$lang['subscription_add'] = 'Подписка успешно добавлена!';
$lang['subscription_delete']= 'Подписка успешно удалена.';

$lang['lasttopicstitle'] = "Последние созданные темы";

#Ошибки
$lang['error_access_category'] = 'Доступ в этот раздел ограничен.';
$lang['error_auth'] = $lang['error'].', необходимо авторизироваться.';
$lang['error_no_forum'] = 'Форум не найден.';
$lang['error_no_right_edit']	 = 'У вас недостаточно прав для редактирования';
$lang['error_no_right_comment_edit'] =$lang['error_no_right_edit'].' этого комментария.';
$lang['error_comment_add'] = 'Ваше сообщение не добавлено по следующим причинам:';
$lang['error_no_data'] = 'Данные не инициализированы.';
$lang['error_no_reg_add'] = 'Чтобы оставлять сообщения на форуме необходимо зарегистрироваться.';
$lang['error_no_message'] =  'Вы не ввели текст сообщения.';
$lang['error_blocking'] =  '<FONT COLOR="#FF0066">Вам запрещено добавлять комментарии в этом разделе сайта.<BR><A HREF="index.php?name=blocking">Узнать за что запрещено.</A></FONT>';
$lang['error_no_title_topic'] = 'Вы не ввели название темы.';
$lang['error_data'] =  'Неверные данные.'	 ;
$lang['error_no_forum'] = 'Укажите раздел форума а не категорию';
$lang['error_no_topics'] = 'Не указаны темы.';
$lang['error_no_messages'] = 'Не указаны сообщения.';
$lang['error_subscription_exists'] = 'Вы уже подписаны на уведомления в этой теме';
$lang['error_file_exists'] = 'Не найден файл ';


$lang['lasttopics'] = 'Последние созданные темы';
$lang['za'] = ' за ';
$lang['last'] = 'последние ';
$lang['day'][1] ='день';
$lang['day'][2] ='дня';
$lang['day'][3] ='дня';
$lang['day'][4] ='дня';
$lang['day'][5] ='дней';
$lang['day'][6] ='дней';
$lang['day'][7] ='дней';
$lang['day'][8] ='дней';
$lang['day'][9] ='дней';
$lang['day'][0] ='дней';

?>