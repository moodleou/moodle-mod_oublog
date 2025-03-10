<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

$string['attachments'] = 'Прикрепленные файлы';
$string['oublog'] = 'OU blog';
$string['modulename'] = 'OU blog';
$string['modulenameplural'] = 'OU blogs';
$string['modulename_help'] = 'Позволяет создавать блоги внутри учебного модуля, независимые от встроенной в Moodle системы блогов. Возможно создание блогов внутри модуля (все пользователи модуля пишут в один блог), групповых блогов и индивидуальных блогов. Соответствующий блогу элемент курса можно переименовать, чтобы название лучше отражало его цель, например "Дневник практики".';

$string['oublogintro'] = 'Вступительный текст';
$string['lastmodified'] = 'Последняя запись: {$a}';
$string['strftimerecent'] = '%d.%m.%Y, %H:%M';

$string['oublog:view'] = 'Просмотр записей';
$string['oublog:addinstance'] = 'Добавить новый блог';
$string['oublog:viewpersonal'] = 'Просмотр записей персонального блога';
$string['oublog:viewprivate'] = 'Просмотр частных записей персонального блога';
$string['oublog:contributepersonal'] = 'Записи и комментарии в персональных блогах';
$string['oublog:post'] = 'Создать новую запись';
$string['oublog:comment'] = 'Добавить комментарий к записи';
$string['oublog:managecomments'] = 'Управление комментариями';
$string['oublog:manageposts'] = 'Управление записями';
$string['oublog:managelinks'] = 'Управление ссылками';
$string['oublog:audit'] = 'Просмотр удаленных записей и старых версий';
$string['oublog:viewindividual'] = 'Просмотр индивидуальных блогов';
$string['oublog:exportownpost'] = 'Экспорт собственной записи';
$string['oublog:exportpost'] = 'Экспорт записи';
$string['oublog:exportposts'] = 'Экспорт записей';
$string['oublog:ignorepostperiod'] = 'Период игнорирования записей';
$string['oublog:ignorecommentperiod'] = 'Период игнорирования комментариев';

$string['advancedoptions'] = 'Расширенные настройки';
$string['limits'] = 'Период участия';
$string['postfrom'] = 'Разрешить записи только&nbsp;с';
$string['postuntil'] = 'Разрешить записи только&nbsp;до';
$string['commentfrom'] = 'Разрешить комментарии только&nbsp;с';
$string['commentuntil'] = 'Разрешить комментарии только&nbsp;до';
$string['beforestartpost'] = 'Вы не можете создать запись сейчас. Создание записей будет доступно с&nbsp;{$a}.';
$string['beforestartpostcapable'] = 'Студенты не могут создавать собственные записи до&nbsp;{$a}.
<br/> У вас есть возможность создания записей до этого времени.';
$string['beforeendpost'] = 'Вы можете создавать записи только до&nbsp;{$a}.';
$string['beforeendpostcapable'] = 'Студенты могут создавать собственные записи до&nbsp;{$a}.
<br/> У вас есть возможность создания записей после этого времени.';
$string['afterendpost'] = 'Вы не можете создать запись сейчас. Создание записей было доступно до&nbsp;{$a}.';
$string['afterendpostcapable'] = 'Студенты могли создавать собственные записи до&nbsp;{$a}.
<br/> У вас есть возможность создания записей после этого времени.';
$string['beforestartcomment'] = 'Вы не можете добавить комментарий сейчас. Комментарии доступны с&nbsp;{$a}.';
$string['beforestartcommentcapable'] = 'Студенты не могут комментировать записи до&nbsp;{$a}.
<br/> У вас есть возможность комментировать до этого времени.';
$string['beforeendcomment'] = 'Вы можете комментировать записи только до&nbsp;{$a}.';
$string['beforeendcommentcapable'] = 'Студенты могут комментировать записи до&nbsp;{$a}.
<br/> У вас есть возможность комментировать после этого времени.';
$string['afterendcomment'] = 'Вы не можете добавить комментарий сейчас. Комментарии были доступны до&nbsp;{$a}.';
$string['afterendcommentcapable'] = 'Студенты могли комментировать записи до&nbsp;{$a}.
<br/> У вас есть возможность комментировать после этого времени.';

$string['mustprovidepost'] = 'Необходимо указать идентификатор записи';
$string['newpost'] = 'Новая запись';
$string['removeblogs'] = 'Удалить все элементы блогов';
$string['title'] = 'Заголовок';
$string['message'] = 'Сообщение';
$string['tags'] = 'Теги';
$string['tagsfield'] = 'Теги (разделенные запятыми)';
$string['allowcomments'] = 'Разрешить комментарии';
$string['allowcommentsmax'] = 'Разрешить комментарии (если это указано для записи)';
$string['logincomments'] = 'Да, от зарегистрированных пользователей';
$string['permalink'] = 'Постоянная ссылка';
$string['publiccomments'] = 'Да, от кого угодно (даже если он не зарегистрирован)';
$string['publiccomments_info'] = 'Если незарегистрированный пользователь добавляет комментарий, вы получите уведомление по электронной почте и сможете подтвердить отображение комментария, или отклонить его. Это необходимо для предотвращения спама.';
$string['error_grouppubliccomments'] = 'Вы не можете разрешить комментарии ото всех, если блог находится в групповом режиме';
$string['nocomments'] = 'Комментарии запрещены';
$string['visibility'] = 'Разрешить просмотр';
$string['visibility_help'] = '
<p><strong>Только участникам курса</strong> &ndash; чтобы просмотреть запись блога, вам должны предоставить доступ к элементу курса, обычно это происходит путём записи на курс, который его содержит.</p>

<p><strong>Всем зарегистрированным пользователям</strong> &ndash; любой зарегистрированный пользователь может просмотреть запись, даже если он не записан на конкретный курс.</p>

<p><strong>Вообще всем</strong> &ndash; любой пользователь Интернета может просмотреть эту запись, если вы поделитесь с ним её адресом.</p>';
$string['maxvisibility'] = 'Максимальная видимость';
$string['yes'] = 'Да';
$string['no'] = 'Нет';
$string['blogname'] = 'Название блога';
$string['summary'] = 'Описание';
$string['statblockon'] = 'Показывать дополнительную статистику использования блога';
$string['statblockon_help'] = 'Включить отображение дополнительной статистики в блоке "Использование блога".
Только для персональных (глобальных), видимых индивидуальных и видимых групповых блогов.';
$string['oublogallpostslogin'] = 'Требовать входа в систему на странице со списком записей';
$string['oublogallpostslogin_desc'] = 'Включить требование входа в систему на странице элементов сайта в персональном блоге. Если включить эту опцию, только вошедшие в систему пользователи увидят ссылку на эту страницу.';

$string['globalusageexclude'] = 'Исключения из статистики глобального использования';
$string['globalusageexclude_desc'] = 'Разделенный запятыми список идентификаторов пользователей, которых необходимо исключить из статистики наиболее активных участников глобального блога';

$string['introonpost'] = 'Отображать вступительный текст при добавлении записи';

$string['displayname_default'] = 'блог';
$string['displayname'] = 'Альтернативное отображаемое имя (оставьте пустым, чтобы использовать значение по умолчанию)';
$string['displayname_help'] = 'Укажите альтернативное название типа элемента курса через интерфейс.

Если оставить значение пустым, будет использовано значение по умолчанию - "блог".

Альтернативное название должно начинаться с маленькой буквы, при необходимости она будет преобразована в заглавную автоматически.';

$string['visibleyou'] = 'Только автору'; // Разрешить просмотр ...
$string['visiblecourseusers'] = 'Только участникам курса';
$string['visibleblogusers'] = 'Только участникам этого блога';
$string['visibleloggedinusers'] = 'Всем зарегистрированным пользователям';
$string['visiblepublic'] = 'Вообще всем';
$string['invalidpostid'] = 'Некорректный идентификатор записи';

$string['addpost'] = 'Добавить запись';
$string['editpost'] = 'Обновить запись';
$string['editsummary'] = 'Отредактировано {$a->editdate} пользователем {$a->editby}';
$string['editonsummary'] = 'Отредактировано {$a->editdate}';

$string['edit'] = 'Редактировать';
$string['delete'] = 'Удалить';

$string['olderposts'] = 'Предыдущие записи';
$string['newerposts'] = 'Более новые записи';
$string['extranavolderposts'] = 'Более старые записи: {$a->from}-{$a->to}';
$string['extranavtag'] = 'Тег: {$a}';

$string['comments'] = 'Комментарии';
$string['recentcomments'] = 'Новые комментарии';
$string['ncomments'] = 'Комментарии ({$a})';
$string['onecomment'] = 'Комментарии ({$a})';
$string['npending'] = 'Комментарии, ожидающие подтверждения ({$a})';
$string['onepending'] = 'Комментарии, ожидающие подтверждения ({$a})';
$string['npendingafter'] = ', ожидает подтверждения: {$a}';
$string['onependingafter'] = ', ожидает подтверждения: {$a}';
$string['comment'] = 'Ваш комментарий';
$string['lastcomment'] = '(последний комментарий от пользователя {$a->fullname}, добавлен {$a->timeposted})';
$string['addcomment'] = 'Добавить комментарий';

$string['confirmdeletepost'] = 'Вы уверены, что хотите удалить эту запись?';
$string['confirmdeletecomment'] = 'Вы уверены, что хотите удалить этот комментарий?';
$string['confirmdeletelink'] = 'Вы уверены, что хотите удалить эту ссылку?';

$string['viewedit'] = 'Показать изменения';
$string['views'] = 'Количество просмотров:';

$string['addlink'] = 'Добавить ссылку';
$string['editlink'] = 'Редактировать ссылку';
$string['links'] = 'Ссылки по теме';

$string['subscribefeed'] = 'Подписаться на ленту новостей (это требует специального программного обеспечения), чтобы получать уведомления, когда {$a} обновится.';
$string['feeds'] = 'Ленты новостей';
$string['blogfeed'] = 'Ленты новостей';
$string['commentsfeed'] = 'Только комментарии';
$string['atom'] = 'Atom';
$string['rss'] = 'RSS';
$string['atomfeed'] = 'Atom feed';
$string['rssfeed'] = 'RSS feed';

$string['newblogposts'] = 'Новые записи';

$string['blogsummary'] = 'Описание блога';
$string['posts'] = 'Записи';

$string['defaultpersonalblogname'] = '{$a->displayname} пользователя {$a->name} ';

$string['numposts'] = 'Количество записей: {$a}';

$string['noblogposts'] = 'Записей нет';

$string['blogoptions'] = 'Настройка блога';

$string['postedby'] = 'от пользователя {$a}';
$string['postedbymoderated'] = 'от пользователя {$a->commenter} (подтверждено {$a->approver} {$a->approvedate})';
$string['postedbymoderatedaudit'] = 'от пользователя {$a->commenter} [{$a->ip}] (подтверждено {$a->approver} {$a->approvedate})';

$string['deletedby'] = 'Удалено пользователем {$a->fullname}, {$a->timedeleted}';

$string['newcomment'] = 'Новый комментарий';
$string['postmessage'] = 'Добавить';

$string['searchthisblog'] = 'Поиск здесь';
$string['searchblogs'] = 'Поиск везде';
$string['searchblogs_help'] = 'Введите поисковый запрос и нажмите клавишу Ввод на клавиатуре или кнопку поиска на экране.

Для поиска точных фраз заключайте слова в кавычки.

Для исключения слова вставьте прямо перед ним символ дефиса.

Например: поисковый запрос <tt>Пикассо -скульптура &quot;ранние работы&quot;</tt> вернет результаты, содержащие &lsquo;Пикассо&rsquo; или фразу &lsquo;ранние работы&rsquo; но исключит элементы, содержащие слово &lsquo;скульптура&rsquo;.';

$string['url'] = 'Полный интернет-адрес';

$string['bloginfo'] = 'информация о блоге';

$string['feedhelp'] = 'Ленты новостей';
$string['feedhelp_help'] = 'Если вы пользуетесь лентами новостей, вы можете добавить ссылки на Atom или RSS, чтобы следить за новыми записями.
Большинство программ для чтения лент новостей поддерживают Atom и RSS.

Если комментарии разрешены, можно подписаться на ленту новостей &lsquo;Только комментарии&rsquo;.';
$string['unsupportedbrowser'] = '<p>Ваш браузер не может самостоятельно отображать ленты новостей Atom или RSS.</p>
<p>Ленты новостей лучше всего подходят для чтения специальными компьютерными программами или сайтами. Если вы хотите использовать эту ленту в такой программе, скопируйте адрес ленты из адресной строки браузера в программу.</p>';

$string['completionpostsgroup'] = 'Требуемое количество записей';
$string['completionpostsgroup_help'] = 'Если вы включите эту опцию, блог для студента будет отмечен как заполненный как только студент сделает в нём указанное количество записей.';
$string['completionposts'] = 'Пользователь должен добавить записи:';
$string['completioncommentsgroup'] = 'Требуемое количество комментариев';
$string['completioncommentsgroup_help'] = 'Если вы включите эту опцию, блог для студента будет отмечен как заполненный как только студент сделает в нём указанное количество комментариев.';
$string['completioncomments'] = 'Пользователь должен добавить комментарии к записям:';

$string['computingguide'] = 'Руководство по OU blogs';
$string['computingguideurl'] = 'Интернет-адрес руководства по обработке данных';
$string['computingguideurlexplained'] = 'Введите URL руководства по обработке данных OU blogs';

$string['maybehiddenposts'] = '{$a->name} может содержать записи, которые разрешено видеть или комментировать только зарегистрированным пользователям. Если у вас есть учетная запись в системе, пожалуйста, <a href=\'{$a->link}\'>войдите, чтобы получить полный доступ</a>.';
$string['guestblog'] = 'Если у вас есть учетная запись в системе, пожалуйста, <a href=\'{$a}\'>войдите, чтобы получить полный доступ</a>.';
$string['noposts'] = 'Доступных записей нет.';
$string['nopostsnotags'] = 'Нет доступных записей с указанным тегом {$a->tag}.';

// Errors.
$string['accessdenied'] = 'Извините, у вас нет доступа к просмотру этой страницы.';
$string['invalidpost'] = 'Некорректный идентификатор записи';
$string['invalidcomment'] = 'Некорректный идентификатор комментария';
$string['invalidblog'] = 'Некорректный идентификатор блога';
$string['invalidedit'] = 'Некорректный идентификатор изменения';
$string['invalidlink'] = 'Некорректный идентификатор ссылки';
$string['personalblognotsetup'] = 'Личные блоги не настроены';
$string['tagupdatefailed'] = 'Не удалось обновить теги';
$string['commentsnotallowed'] = 'Комментарии запрещены';
$string['couldnotaddcomment'] = 'Не удалось добавить комментарий';
$string['onlyworkspersonal'] = 'Эта возможность предусмотрена только для личных блогов';
$string['couldnotaddlink'] = 'Не удалось добавить ссылку';
$string['notaddpostnogroup'] = 'Невозможно добавить запись без группы';
$string['notaddpost'] = 'Не удалось добавить запись';
$string['feedsnotenabled'] = 'Ленты новостей отключены';
$string['invalidformat'] = 'Формат должен быть atom либо rss';
$string['deleteglobalblog'] = 'Невозможно удалить глобальный блог';
$string['globalblogmissing'] = 'Глобальный блог отсутствует';
$string['invalidvisibility'] = 'Некорректный уровень видимости';
$string['invalidvisbilitylevel'] = 'Некорректный уровень видимости {$a}';
$string['invalidblogdetails'] = 'Не удалось найти подробную информацию по записи {$a}';

$string['siteentries'] = 'Просмотр элементов сайта';
$string['overviewnumentrylog1'] = 'элемент с момента последнего входа';
$string['overviewnumentrylog'] = 'элементов с момента последнего входа';
$string['overviewnumentryvw1'] = 'элемент с момента последнего просмотра';
$string['overviewnumentryvw'] = 'элементов с момента последнего просмотра';

$string['individualblogs'] = 'Индивидуальные блоги';
$string['no_blogtogetheroringroups'] = 'Нет (блог ведётся всеми совместно или в группах)';
$string['separateindividualblogs'] = 'Отдельные индивидуальные блоги';
$string['visibleindividualblogs'] = 'Видимые индивидуальные блоги';

$string['separateindividual'] = 'Отображаются&nbsp;недоступные&nbsp;другим&nbsp;записи&nbsp;пользователя';
$string['visibleindividual'] = 'Отображаются&nbsp;записи&nbsp;пользователя';
$string['viewallusers'] = 'Все пользователи';
$string['viewallusersingroup'] = 'Все пользователи в группе';

$string['re'] = 'Re: {$a}';

$string['moderated_info'] = 'Вы не вошли в систему, поэтому ваш комментарий станет видимым только после проверки модератором. Если у вас есть учетная запись в системе, пожалуйста, <a href=\'{$a}\'>войдите, чтобы получить полный доступ</a>.';
$string['moderated_authorname'] = 'Ваше имя';
$string['moderated_confirmvalue'] = 'да';
$string['moderated_confirminfo'] = 'Пожалуйста введите <strong>да</strong> в текстовое поле ниже, чтобы подтвердить, что вы не робот.';
$string['moderated_confirm'] = 'Подтверждение';
$string['moderated_addedcomment'] = 'Спасибо за ваш комментарий, он был сохранен. Комментарий будет скрыт до тех пор, пока не пройдёт проверку и подтверждение автором записи.';
$string['moderated_submitted'] = 'Ожидание модерации';
$string['moderated_typicaltime'] = 'Обычно это занимает примерно {$a}.';
$string['error_noconfirm'] = 'Введите текст, выделенный жирным шрифтом, в это поле, в точности так, как указано выше.';
$string['error_toomanycomments'] = 'За последний час вы добавили слишком много комментариев с этого интернет-адреса. Пожалуйста, подождите какое-то время, потом попробуйте снова.';
$string['moderated_awaiting'] = 'Комментарии, ожидающие подтверждения';
$string['moderated_awaitingnote'] = 'Эти комментарии не видны другим пользователям, пока вы их не подтвердите. Помните, что авторы комментариев неизвестны системе. Комментарии могут содержать ссылки, пройдя по которым вы можете нанести <strong>серьёзный вред вашему компьютеру</strong>. При малейшем сомнении, отклоняйте комментарии <strong>не переходя ни по каким ссылкам</strong>.';
$string['moderated_postername'] = 'под именем <strong>{$a}</strong>';
$string['error_alreadyapproved'] = 'Комментарий уже подтвержден или отклонен';
$string['error_wrongkey'] = 'Ключ комментария некорректен';
$string['error_unspecified'] = 'Система не может завершить запрос из-за возникновения ошибки ({$a})';
$string['error_moderatednotallowed'] = 'Модерация комментариев больше не разрешена в этом блоге или записи блога';
$string['moderated_approve'] = 'Подтвердить этот комментарий';
$string['moderated_reject'] = 'Отклонить этот комментарий';
$string['moderated_rejectedon'] = 'Отклонен {$a}:';
$string['moderated_restrictpost'] = 'Ограничить комментарии к этой записи';
$string['moderated_restrictblog'] = 'Ограничить комментарии ко всем вашим записям в этом блоге';
$string['moderated_restrictpage'] = 'Ограничить комментарии';
$string['moderated_restrictpost_info'] = 'Хотели бы вы ограничить комментарии к этой записи, чтобы оставлять комментарии могли только зарегистрированные пользователи?';
$string['moderated_restrictblog_info'] = 'Хотели бы вы ограничить комментарии ко всем вашим записям в этом блоге, чтобы оставлять комментарии могли только зарегистрированные пользователи?';
$string['moderated_emailsubject'] = 'Комментарий ждет подтверждения: {$a->blog} ({$a->commenter})';
$string['moderated_emailhtml'] =
'<p>(Это письмо создано автоматически. Пожалуйста, не отвечайте на него.)</p>
<p>Кто-то добавил комментарий к вашей записи в блоге: {$a->postlink}</p>
<p>Вам нужно <strong>подтвердить комментарий</strong> прежде чем он станет доступен читателям.</p>
<p>Автор комментария неизвестен системе. Комментарий может содержать ссылки,
пройдя по которым вы можете нанести <strong>серьёзный вред вашему компьютеру</strong>.
При малейшем сомнении, отклоняйте комментарий <strong>не переходя ни по каким ссылкам</strong>.</p>
<p>Если вы подтверждаете комментарий, вы принимаете ответственность за его размещение. Убедитесь, что он не содержит чего-то, противоречащего правилам.</p>
<hr/>
<p>Указанное имя: {$a->commenter}</p>
<hr/>
<h3>{$a->commenttitle}</h3>
{$a->comment}
<hr/>
<ul class=\'oublog-approvereject\'>
<li><a href=\'{$a->approvelink}\'>{$a->approvetext}</a></li>
<li><a href=\'{$a->rejectlink}\'>{$a->rejecttext}</a></li>
</ul>
<p>
Вы можете проигнорировать это письмо. Комментарий будет отклонен автоматически через 30 дней.
</p>
<p>
Если вы получаете слишком много таких писем, возможно вы захотите ограничить комментарии, оставив эту возможность только зарегистрированным пользователям.
</p>
<ul class=\'oublog-restrict\'>
<li><a href=\'{$a->restrictpostlink}\'>{$a->restrictposttext}</a></li>
<li><a href=\'{$a->restrictbloglink}\'>{$a->restrictblogtext}</a></li>
</ul>';
$string['moderated_emailtext'] =
'Это письмо создано автоматически. Пожалуйста, не отвечайте на него.

Кто-то добавил комментарий к вашей записи в блоге:
{$a->postlink}

Вам нужно подтвердить комментарий прежде чем он станет доступен
читателям.

Автор комментария неизвестен системе. Комментарий может содержать
ссылки, пройдя по которым вы можете нанести серьёзный вред вашему
компьютеру. При малейшем сомнении, отклоняйте комментарий не переходя
ни по каким ссылкам.

Если вы подтверждаете комментарий, вы принимаете ответственность
за его размещение. Убедитесь, что он не содержит чего-то,
противоречащего правилам.

-----------------------------------------------------------------------
Указанное имя: {$a->commenter}
-----------------------------------------------------------------------
{$a->commenttitle}
{$a->comment}
-----------------------------------------------------------------------

* {$a->approvetext}:
  {$a->approvelink}

* {$a->rejecttext}:
  {$a->rejectlink}

Вы можете проигнорировать это письмо. Комментарий будет отклонен
автоматически через 30 дней.

Если вы получаете слишком много таких писем, возможно вы захотите
ограничить комментарии, оставив эту возможность
только зарегистрированным пользователям.

* {$a->restrictposttext}:
  {$a->restrictpostlink}

* {$a->restrictblogtext}:
  {$a->restrictbloglink}
';

$string['displayversion'] = 'Версия OU Blog: <strong>{$a}</strong>';

$string['pluginadministration'] = 'Администрирование OU Blog';
$string['pluginname'] = 'OU Blog';
// Help strings.
$string['allowcomments_help'] = '<strong>Да, от зарегистрированных пользователей</strong> разрешает комментарии от пользователей, у которых есть доступ к записи.

<strong>Да, от кого угодно</strong> разрешает комментарии и от зарегистрированных, и от незарегистрированных пользователей. Вы получите уведомление по электронной почте о необходимости подтвердить или отклонить комментарии от незарегистрированных пользователей.

<strong>Нет</strong> запрещает всем оставлять комментарии к этой записи.';
$string['individualblogs_help'] = '
<p><strong>Нет (блог ведётся всеми совместно или в группах)</strong>: <em>Индивидуальные блоги не используются</em> &ndash;
Индивидуальные блоги отсутствуют, каждый участвует в одном большом сообществе (в зависимости от настройки режима групп).</p>
<p><strong>Отдельные индивидуальные блоги</strong>: <em>Индивидуальные блоги используются их авторами</em> &ndash;
Пользователи могут пополнять и просматривать только свои собственные блоги, за исключением пользователей, имеющих  разрешение ("viewindividual") на просмотр индивидуальных блогов других пользователей.</p>
<p><strong>Видимые индивидуальные блоги</strong>: <em>Индивидуальные блоги доступны всем</em> &ndash;
Пользователи могут добавлять записи только в свои собственные блоги, но могут просматривать записи в чужих индивидуальных блогах.</p>';

$string['maxvisibility_help'] = '
<p><em>Для персонального блога:</em> <strong>Только автору блога</strong> &ndash;
больше никто* не может просмотреть эту запись.</p>
<p><em>Для блога внутри курса:</em> <strong>Только участникам курса</strong> &ndash; чтобы просмотреть запись вам необходимо получить доступ к блогу, обычно для этого нужно записаться на курс, который его содержит.</p>

<p><strong>Всем зарегистрированным пользователям</strong> &ndash; любой зарегистрированный пользователь может просмотреть запись в блоге, даже если он не записан на соответствующий курс.</p>
<p><strong>Вообще всем</strong> &ndash; любой пользователь Интернета может просмотреть эту запись, если вы поделитесь с ним адресом блога.</p>

<p>Этот параметр настраивается как для блога в целом, так и для отдельных записей. Если значение задано на уровне блога, оно определяет максимальное возможное значение для записей. Например, если для всего блога оно установлено на первый уровень, то вы не сможете менять значение для отдельных записей.</p>';
$string['tags_help'] = 'Теги и метки, которые помогают вам упорядочить и найти записи.';
// Used at OU only.
$string['externaldashboardadd'] = 'Добавить блог на стартовый экран';
$string['externaldashboardremove'] = 'Удалить блог со стартового экрана';
$string['viewblogdetails'] = 'Просмотр подробной информации о блоге';
$string['viewblogposts'] = 'Вернуться к блогу';

// User participation.
$string['oublog:grade'] = 'Оценивать активность пользователей в блоге';
$string['oublog:viewparticipation'] = 'Просмотр активности пользователей в блоге';
$string['userparticipation'] = 'Активность пользователей';
$string['usersparticipation'] = 'Активность всех пользователей';
$string['myparticipation'] = 'Моя активность';
$string['savegrades'] = 'Сохранить оценки';
$string['participation'] = 'Вся активность';
$string['participationbyuser'] = 'Активность пользователей';
$string['details'] = 'Подробнее';
$string['foruser'] = ' пользователя {$a}';
$string['postsby'] = 'Записи пользователя {$a}';
$string['commentsby'] = 'Комментарии пользователя {$a}';
$string['commentonby'] = 'Комментарии пользователя <u>{$a->author}</u> к записи <u>{$a->title}</u> от {$a->date}';
$string['nouserposts'] = 'Записи отсутствуют.';
$string['nousercomments'] = 'Комментарии отсутствуют.';
$string['gradesupdated'] = 'Оценки обновлены';
$string['usergrade'] = 'Оценка пользователя';
$string['nousergrade'] = 'У пользователя нет оценки.';

// Participation download strings.
$string['downloadas'] = 'Загрузить данные в формате';
$string['downloadcsv'] = 'Текст с разделителями-запятыми (CSV)';
$string['postauthor'] = 'Автор записи';
$string['postdate'] = 'Дата добавления записи';
$string['posttime'] = 'Время добавления записи';
$string['posttitle'] = 'Заголовок записи';

// Export.
$string['exportedpost'] = 'Экспортируемая запись';
$string['exportpostscomments'] = ' все видимые на данный момент записи и комментарии к ним.';
$string['exportuntitledpost'] = 'Запись без заголовка ';

$string['configmaxattachments'] = 'Максимальное количество файлов, которое разрешено прикладывать к записи в блоге (значение по умолчанию).';
$string['configmaxbytes'] = 'Максимальный размер всех файлов, приложенных к блогам этого сайта (значение по умолчанию, с учетом ограничений курса и других локальных настроек).';
$string['maxattachmentsize'] = 'Максимальный размер приложенного файла';
$string['maxattachments'] = 'Максимальное количество приложенных файлов';
$string['maxattachments_help'] = 'Этот параметр определяет максимальное количество файлов, которое можно приложить к записи в блоге.';
$string['maxattachmentsize_help'] = 'Этот параметр определяет размер самого большого файла или изображения, который можно использовать в записи блога.';
$string['attachments_help'] = 'Если хотите, вы можете приложить к записи в блоге один или несколько файлов. Если вы приложите изображение, оно будет отображено после текста записи.';

$string['remoteserver'] = 'Импорт с удаленного сервера';
$string['configremoteserver'] = 'Адрес корневой директории (wwwroot) удаленного сервера, который нужно использовать для импорта записей.
При импорте записей блоги удаленного сервера будут показаны вместе с блогами локального сайта.';
$string['remotetoken'] = 'Токен для импорта с удаленного сервера';
$string['configremotetoken'] = 'Пользовательский токен для доступа к веб-сервисам, работающим на удаленном сервере, с которого выполняется импорт.';

$string['reportingemail'] = 'Адреса электронной почты для сообщений о проблемах';
$string['reportingemail_help'] = 'Здесь можно указать адреса электронной почты, на которые будут отправляться сообщения о проблемах с записями или комментариями в блогах. Адреса вводятся через запятую.';
$string['postalert'] = 'Сообщить о проблеме с записью';
$string['commentalert'] = 'Сообщить о проблеме с комментарием';
$string['oublog_managealerts'] = 'Управление сообщениями о проблемах с записями и комментариями';
$string['untitledpost'] = 'Запись без заголовка';
$string['untitledcomment'] = 'Комментарий без заголовка';

// Discovery block.
$string['discovery'] = '{$a}: обзор';
$string['timefilter_alltime'] = 'За всё время';
$string['timefilter_thismonth'] = 'За месяц';
$string['timefilter_thisyear'] = 'За год';
$string['timefilter_label'] = 'Период: ';
$string['timefilter_submit'] = 'Обновить';
$string['timefilter_open'] = 'Показать настройки';
$string['timefilter_close'] = 'Скрыть настройки';
$string['visits'] = 'Самые посещаемые';
$string['activeblogs'] = 'Активные';
$string['numberviews'] = 'Просмотров: {$a}';
$string['visits_info_alltime'] = 'Больше всего просмотров за всё время';
$string['visits_info_active'] = 'Активные (есть хотя бы одна запись за прошедший месяц) с наибольшим количеством просмотров';
$string['mostposts'] = 'Больше всего записей';
$string['numberposts'] = 'Записей: {$a}';
$string['posts_info_alltime'] = 'Больше всего записей за все время';
$string['posts_info_thisyear'] = 'Больше всего записей за прошедший год';
$string['posts_info_thismonth'] = 'Больше всего записей записей за прошедший месяц';
$string['mostcomments'] = 'Больше всего комментариев';
$string['numbercomments'] = 'Комментариев: {$a}';
$string['comments_info_alltime'] = 'Больше всего комментариев за все время';
$string['comments_info_thisyear'] = 'Больше всего комментариев за прошедший год';
$string['comments_info_thismonth'] = 'Больше всего комментариев за прошедший месяц';
$string['commentposts'] = 'Комментируемые записи';
$string['commentposts_info_alltime'] = 'Записи с наибольшим количеством комментариев за все время';
$string['commentposts_info_thisyear'] = 'Записи с наибольшим количеством комментариев за прошедший год';
$string['commentposts_info_thismonth'] = 'Записи с наибольшим количеством комментариев за прошедший месяц';

// Delete and Email.
$string['emailcontenthtml'] = 'Сообщаем вам, что ваша запись была удалена пользователем \'{$a->firstname} {$a->lastname}\'. Подробная информация о записи:<br />
<br />
Заголовок: {$a->subject}<br />
{$a->activityname}: {$a->blog}<br />
Курс: {$a->course}<br />
<br />
<a href={$a->deleteurl}>Посмотреть удаленную запись</a>';
$string['deleteemailpostbutton'] = 'Удалить и сообщить на e-mail';
$string['deleteandemail'] = 'Удалить и сообщить на e-mail';
$string['emailmessage'] = 'Текст письма';
$string['cancel'] = 'Отмена';
$string['deleteemailpostdescription'] = 'Нажмите чтобы удалить запись и, если нужно, отправить электронное письмо с уведомлением об этом. Текст уведомления можно менять.';
$string['copytoself'] = 'Отправить копию себе';
$string['includepost'] = 'Включая текст записи';
$string['deletedblogpost'] = 'Запись без заголовка.';
$string['emailerror'] = 'При отправке письма произошла ошибка';
$string['sendanddelete'] = 'Отправить и удалить';
$string['extra_emails'] = 'Адреса электронной почты других получателей';
$string['extra_emails_help'] = 'Введите один или несколько адресов. Несколько адресов разделяются пробелом или точками с запятой.';

// Import pages.
$string['allowimport'] = 'Разрешить импорт записей';
$string['allowimport_help'] = 'Разрешить любому пользователю импортировать страницы из других блогов (в составе курсов), к которым они имеют доступ.';
$string['allowimport_invalid'] = 'Записи можно импортировать только если элемент курса настроен на индивидуальный режим.';
$string['import'] = 'Импорт';
$string['import_notallowed'] = 'Импорт записей запрещен.';
$string['import_step0_nonefound'] = 'У вас нет доступа ни к одному элементу курса, из которого можно было бы импортировать записи.';
$string['import_step0_inst'] = 'Ниже приведен список блогов, вы можете импортировать их целиком или выбрать отдельные записи.';
$string['import_step0_numposts'] = '(записей: {$a})';
$string['import_step0_blog'] = 'Импортировать блог';
$string['import_step0_selected_posts'] = 'Импортировать отдельные записи';
$string['import_step1_inst'] = 'Выберите записи для импорта:';
$string['import_step1_from'] = 'Импорт из:';
$string['import_step1_table_title'] = 'Заголовок';
$string['import_step1_table_posted'] = 'Дата создания';
$string['import_step1_table_tags'] = 'Теги';
$string['import_step1_table_include'] = 'Включить в импорт';
$string['import_step1_addtag'] = 'Фильтр по тегу - {$a}';
$string['import_step1_removetag'] = 'Удалить фильтр по тегу - {$a}';
$string['import_step1_include_label'] = 'Импорт записи - {$a}';
$string['import_step1_submit'] = 'Импортировать';
$string['import_step1_all'] = 'Выбрать все';
$string['import_step1_none'] = 'Отменить выбор';
$string['import_step2_inst'] = 'Импортируются записи:';
$string['import_step2_none'] = 'Не выбрано ни одной записи для импорта.';
$string['import_step2_prog'] = 'Выполняется импорт';
$string['import_step2_total'] = 'Импорт успешно завершен, импортировано записей: {$a}';
$string['import_step2_conflicts'] = 'Некоторые импортируемые записи конфликтуют с уже имеющимися. Количество конфликтующих записей: {$a}';
$string['import_step2_conflicts_submit'] = 'Импортировать конфликтующие записи';

// My Participation.
$string['contribution'] = 'Активность';
$string['contribution_all'] = 'Активность за все время';
$string['contribution_from'] = 'Активность с {$a}';
$string['contribution_to'] = 'Активность по {$a}';
$string['contribution_fromto'] = 'Активность с {$a->start} по {$a->end}';
$string['start'] = 'Начальная дата';
$string['end'] = 'Конечная дата';
$string['displayperiod'] = 'Выбор начальной и конечной даты.';
$string['info'] = 'Активность в течение указанного периода.';
$string['displayperiod_help'] = '<p>По умолчанию выбраны все записи.</p>
<p>Вы можете выбрать записи от начальной даты до сегодня.</p>
<p>Вы можете выбрать записи между начальной и конечной датой.</p>
<p>Вы также можете выбрать записи с самого начала до конечной даты.</p>';
$string['nouserpostsfound'] = 'В течение указанного периода записей не было.';
$string['nousercommentsfound'] = 'В течение указанного периода комментариев не было.';
$string['numberpostsmore'] = 'Показать остальные записи ({$a})';
$string['numbercommentsmore'] = 'Показать остальные комментарии ({$a}';
$string['viewmyparticipation'] = 'Подробнее';
$string['viewallparticipation'] = 'Подробнее';
$string['timestartenderror'] = 'Конечная дата не может быть раньше начальной';

$string['savefailtitle']='Не удалось сохранить запись';
$string['savefailnetwork'] = '<p>К сожалению, сейчас не удалось сохранить ваши изменения.
Это может быть связано с ошибкой сети, недоступностью веб-сайта или вашим выходом из своей учетной записи.</p>
<p>Сохранение было прекращено.
Чтобы ваши изменения сохранились, вам необходимо скопировать текст, который вы редактировали, снова войти на страницу редактирования и там вставить скопированный текст.</p>';

$string['order'] = 'Порядок:';
$string['alpha'] = 'По алфавиту';
$string['use'] = 'По частоте использования';
$string['order_help'] = 'Вы можете упорядочить список используемых тегов либо по алфавиту,
либо по количеству записей, в которых эти теги используются (по частоте использования).
Переключаться между этими способами можно с помощью двух ссылок. Система запомнит ваш выбор и будет использовать его в дальнейшем.';
$string['predefinedtags'] = 'Заранее заданные теги';
$string['predefinedtags_help'] = 'Список тегов, которые пользователи могут выбирать при создании записей.
Теги должны быть разделены запятой.';
$string['official'] = 'Используется';
$string['invalidblogtags'] = 'Некорректные теги блога';
$string['nouserpostpartsfound'] = 'В течение указанного периода записей не было.';
$string['nousercommentpartsfound'] = 'В течение указанного периода комментариев не было.';
$string['participation_all'] = 'Активность за все время';
$string['participation_from'] = 'Активность с {$a}';
$string['participation_to'] = 'Активность по {$a}';
$string['participation_fromto'] = 'Активность с {$a->start} по {$a->end}';
$string['recentposts'] = 'Новые записи';
$string['commentonbyusers'] = 'Комментарий <u>{$a->commenttitle}</u> к записи <u>{$a->posttitle}</u> <br> от пользователя <u>{$a->author}</u>';
$string['commentdated'] = 'Добавлен';
$string['postinfoblock'] = '<u>{$a->posttitle}</u> <br> <u>{$a->postdate}</u> <br> <u>{$a->sourcelink}</u>';
$string['postdetail'] = 'Подробно о записи';
$string['group'] = 'Группа ';
$string['event:postcreated'] = 'Запись создана';
$string['event:commentcreated'] = 'Комментарий создан';
$string['event:commentdeleted'] = 'Комментарий удален';
$string['event:postdeleted'] = 'Запись удалена';
$string['event:postupdated'] = 'Запись обновлена';
$string['event:postviewed'] = 'Запись просмотрена';
$string['event:commentapproved'] = 'Комментарий подтвержден';
$string['event:participationviewed'] = 'Просмотр активности';
$string['event:savefailed'] = 'Проблема с сессией при сохранении записи';
$string['event:siteentriesviewed'] = 'Просмотр элементов сайта';
$string['event:postimported'] = 'Запись импортирована';
$string['oublog:rate'] = 'Может оценивать записи для формирования рейтинга.';
$string['oublog:viewallratings'] = 'Просмотр отдельных рейтинговых оценок, данных каждым пользователем';
$string['oublog:viewanyrating'] = 'Просмотр рейтинга любого пользователя';
$string['oublog:viewrating'] = 'Просмотр вашего рейтинга';
$string['grading'] = 'Выставление оценок';
$string['grading_help'] = 'Если вы включите эту опцию, оценка за этот блог будет добавлена в журнал оценок курса и рассчитана автоматически.
Оставьте опцию выключенной для блогов, не предполагающих выставление оценок, либо если вы планируете выставлять оценки вручную.';
$string['grading_invalid'] = 'Выставление оценок за записи требует либо настройки выставления оценок (grade) либо настройки подсчета рейтинга (rate).';
$string['nograde'] = 'Без оценок (по умолчанию)';
$string['teachergrading'] = 'Преподаватель выставляет оценки студентам';
$string['userrating'] = 'Использовать рейтинговые оценки';
$string['share'] = 'Поделиться записью';
$string['tweet'] = 'Твитнуть';
$string['oublogcrontask'] = 'Задачи по обслуживанию OU blog';

$string['restricttags'] = 'Требования к тегам';
$string['restricttags_req'] = 'Сделать указание тегов обязательным';
$string['restricttags_req_set'] = 'Сделать обязательным указание заранее заданных тегов';
$string['restricttags_set'] = 'Разрешить только заранее заданные теги';
$string['restricttags_help'] = 'С помощью этого параметра вы можете ввести требования к тегам, разрешая только заранее заданные теги и/или требуя указания хотя бы одного тега при создании записи.';
$string['restricttagslist'] = 'Вы можете указать только заранее заданные теги: {$a}';
$string['restricttagsvalidation'] = 'Разрешено указывать только заранее заданные теги';
