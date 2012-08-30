<?php
$string['attachments'] = "Attachments";
$string['oublog'] = 'OU blog';
$string['modulename'] = 'OU blog';
$string['modulenameplural'] = 'OU blogs';

$string['oublog:view'] = 'View posts';
$string['oublog:addinstance'] = 'Add a new OU blog';
$string['oublog:viewpersonal'] = 'View posts in personal blogs';
$string['oublog:contributepersonal'] = 'Post and comment in personal blogs';
$string['oublog:post'] = 'Create a new post';
$string['oublog:comment'] = 'Comment on a post';
$string['oublog:managecomments'] = 'Manage comments';
$string['oublog:manageposts'] = 'Manage posts';
$string['oublog:managelinks'] = 'Manage links';
$string['oublog:audit'] = 'View deleted posts and old versions';
$string['oublog:viewindividual'] = 'View individual blogs';
$string['oublog:exportownpost'] = 'Export own post';
$string['oublog:exportpost'] = 'Export post';
$string['mustprovidepost'] = 'Must provide postid';
$string['newpost'] = 'New blog post';
$string['title'] = 'Title';
$string['message'] = 'Message';
$string['tags'] = 'Tags';
$string['tagsfield'] = 'Tags (separated by commas)';
$string['allowcomments'] = 'Allow comments';
$string['allowcommentsmax'] = 'Allow comments (if chosen for post)';
$string['logincomments'] = 'Yes, from logged-in users';
$string['permalink'] = 'Permalink';
$string['publiccomments'] = 'Yes, from everybody (even if not logged in)';
$string['publiccomments_info'] = 'If somebody adds a comment when they are not
logged in, you will receive email notification and can approve the comment for
display, or reject it. This is necessary in order to prevent spam.';
$string['error_grouppubliccomments'] = 'You cannot allow public comments when the blog is in group mode';
$string['nocomments'] = 'Comments not allowed';
$string['visibility'] = 'Who can read this?';
$string['visibility_help'] = '
<p><strong>Visible to participants on this course</strong> &ndash; to view the post you must
have been granted access to the blog, usually by being enrolled on the course that contains it.</p>

<p><strong>Visible to everyone who is logged in to the system</strong> &ndash; everyone who is
logged in can view the post, even if they\'re not enrolled on a specific course.</p>
<p><strong>Visible to anyone in the world</strong> &ndash; any Internet user can see this post
if you give them the blog\'s address.</p>';
$string['maxvisibility'] = 'Maximum visibility';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['blogname'] = 'Blog name';
$string['summary'] = 'Summary';

$string['visibleyou'] = 'Visible only to the blog owner (private)';
$string['visiblecourseusers'] = 'Visible to participants on this course';
$string['visibleblogusers'] = 'Visible only to members of this blog';
$string['visibleloggedinusers'] = 'Visible to everyone who is logged in to the system';
$string['visiblepublic'] = 'Visible to anyone in the world';
$string['invalidpostid'] = 'Invalid Postid';

$string['addpost'] = 'Add blog post';
$string['editpost'] = 'Update blog post';
$string['editsummary'] = 'Edited by {$a->editby}, {$a->editdate}';
$string['editonsummary'] = 'Edited {$a->editdate}';

$string['edit'] = 'Edit';
$string['delete'] = 'Delete';

$string['olderposts'] = '&lt; Older posts';
$string['newerposts'] = 'Newer posts &gt;';
$string['extranavolderposts'] = 'Older posts: {$a->from}-{$a->to}';
$string['extranavtag'] = 'Tag: {$a}';

$string['comments'] = 'Comments';
$string['ncomments'] = '{$a} comments';
$string['onecomment'] = '{$a} comment';
$string['npending'] = '{$a} comments awaiting approval';
$string['onepending'] = '{$a} comment awaiting approval';
$string['npendingafter'] = ', {$a} awaiting approval';
$string['onependingafter'] = ', {$a} awaiting approval';
$string['comment'] = 'Add your comment';
$string['lastcomment'] = '(latest by {$a->fullname}, {$a->timeposted})';
$string['addcomment'] = 'Add comment';

$string['confirmdeletepost'] = 'Are you sure you want to delete this blog post?';
$string['confirmdeletecomment'] = 'Are you sure you want to delete this comment?';
$string['confirmdeletelink'] = 'Are you sure you want to delete this link?';

$string['viewedit'] = 'View edit';
$string['views'] = 'Total visits to this blog:';

$string['addlink'] = 'Add link';
$string['editlink'] = 'Edit link';
$string['links'] = 'Related links';

$string['subscribefeed'] = 'Subscribe to a feed (requires appropriate software) to receive notification when this blog is updated.';
$string['feeds'] = 'Feeds';
$string['blogfeed'] = 'Blog feeds';
$string['commentsfeed'] = 'Comments only';
$string['atom'] = 'Atom';
$string['rss'] = 'RSS';
$string['atomfeed'] = 'Atom feed';
$string['rssfeed'] = 'RSS feed';

$string['newblogposts'] = 'New blog posts';

$string['blogsummary'] = 'Blog summary';
$string['posts'] = 'Posts';

$string['defaultpersonalblogname'] = '{$a}\'s blog';

$string['numposts'] = '{$a} posts';

$string['noblogposts'] = 'No blog posts';

$string['blogoptions'] = 'Blog options';

$string['postedby'] = 'by {$a}';
$string['postedbymoderated'] = 'by {$a->commenter} (approved by {$a->approver}, {$a->approvedate})';
$string['postedbymoderatedaudit'] = 'by {$a->commenter} [{$a->ip}] (approved by {$a->approver}, {$a->approvedate})';

$string['deletedby'] = 'Deleted by {$a->fullname}, {$a->timedeleted}';

$string['newcomment'] = 'New blog comment';

$string['searchthisblog'] = 'Search this blog';
$string['searchblogs'] = 'Search blogs';
$string['searchthisblog_help'] = 'Type your search term and press Enter or click the button.

To search for exact phrases use quote marks.

To exclude a word insert a hyphen immediately before the word.

Example: the search term <tt>picasso -sculpture &quot;early works&quot;</tt> will return results for &lsquo;picasso&rsquo; or the phrase &lsquo;early works&rsquo; but will exclude items containing &lsquo;sculpture&rsquo;.';

$string['url']='Full Web address';

$string['bloginfo']='blog information';

$string['feedhelp']='Feeds';
$string['feedhelp_help']='If you use feeds you can add Atom or RSS links in order to keep up to date with this blog. Most feed readers support Atom and RSS.

If the blog allows comments there are feeds for &lsquo;Comments only&rsquo;.';
$string['unsupportedbrowser']='<p>Your browser cannot display Atom or RSS feeds directly.</p>
<p>Feeds are most useful in separate computer programs or websites. If you want
to use this feed in such a program, copy and paste the address from your browser\'s
address bar.</p>';

$string['completionpostsgroup']='Require posts';
$string['completionpostsgroup_help']='If you enable this option, the blog will be marked as complete for a student once they have made the specified number of posts.';
$string['completionposts']='User must make blog posts:';
$string['completioncommentsgroup']='Require comments';
$string['completioncommentsgroup_help']='If you enable this option, the blog will be marked as complete for a student once they have left the specified number of comments.';
$string['completioncomments']='User must make comments on blog posts:';

$string['computingguide']='Guide to OU blogs';
$string['computingguideurl']='Computing guide URL';
$string['computingguideurlexplained']='Enter the URL for the OU blogs omputing guide';

$string['maybehiddenposts']='This blog might contain posts that are only
visible to logged-in users, or where only logged-in users can comment. If you
have an account on the system, please <a href=\'{$a}\'>log in for full blog access</a>.';
$string['guestblog']='If you have an account on the system, please
<a href=\'{$a}\'>log in for full blog access</a>.';
$string['noposts']='There are no visible posts in this blog.';

//Errors
$string['accessdenied']='Sorry: you do not have access to view this page.';
$string['invalidpost'] = 'Invalid Post Id';
$string['invalidcomment'] = 'Invalid Comment Id';
$string['invalidblog'] = 'Invalid Blog Id';
$string['invalidedit'] = 'Invalid Edit Id';
$string['invalidlink'] = 'Invalid Link Id';
$string['personalblognotsetup'] = 'Personal blogs not set up';
$string['tagupdatefailed'] = 'Failed to update tags';
$string['commentsnotallowed'] = 'Comments are not allowed';
$string['couldnotaddcomment'] = 'Could not add comment';
$string['onlyworkspersonal'] = 'Only works for personal blogs';
$string['couldnotaddlink'] = 'Could not add link';
$string['notaddpostnogroup'] = 'Can\'t add post with no group';
$string['notaddpost'] = 'Could not add post';
$string['feedsnotenabled'] = 'Feeds are not enabled';
$string['invalidformat'] = 'Format must be atom or rss';
$string['deleteglobalblog'] = 'You can\'t delete the global blog';
$string['globalblogmissing'] = 'Global blog is missing';
$string['invalidvisibility'] = 'Invalid visbility level';
$string['invalidvisbilitylevel'] = 'Invalid visibility level {$a}';
$string['invalidblogdetails'] = 'Can\'t find details for blog post {$a}';





$string['siteentries'] = 'View site entries';
$string['overviewnumentrylog1'] = 'entry since last log in';
$string['overviewnumentrylog'] = 'entries since last log in';
$string['overviewnumentryvw1'] = 'entry since last viewed';
$string['overviewnumentryvw'] = 'entries since last viewed';

$string['individualblogs'] = 'Individual blogs';
$string['no_blogtogetheroringroups'] = 'No (blog together or in groups)';
$string['separateindividualblogs'] = 'Separate individual blogs';
$string['visibleindividualblogs'] = 'Visible individual blogs';

$string['separateindividual'] = 'Separate&nbsp;individuals';
$string['visibleindividual'] = 'Visible&nbsp;individuals';
$string['viewallusers'] = 'View all users';
$string['viewallusersingroup'] = 'View all users in group';

$string['re']='Re: {$a}';

$string['moderated_info'] = 'Because you are not logged in, your comment will
only appear after it has been approved. If you
have an account on the system, please <a href=\'{$a}\'>log in for full blog access</a>.';
$string['moderated_authorname'] = 'Your name';
$string['moderated_confirmvalue'] = 'yes';
$string['moderated_confirminfo'] = 'Please enter <strong>yes</strong> below to confirm that you are a person.';
$string['moderated_confirm'] = 'Confirmation';
$string['moderated_addedcomment'] = 'Thank you for adding your comment, which has been successfully received. Your comment won\'t appear until it has been approved by the author of this post.';
$string['moderated_submitted'] = 'Awaiting moderation';
$string['moderated_typicaltime'] = 'In the past, this has usually taken about {$a}.';
$string['error_noconfirm'] = 'Enter the bold text above, exactly as given, into this box.';
$string['error_toomanycomments'] = 'You have made too many blog comments in the past hour from this internet address. Please wait a while then try again.';
$string['moderated_awaiting'] = 'Comments awaiting approval';
$string['moderated_awaitingnote'] = 'These comments are not visible to other users unless you approve them. Bear in mind that the system does not know the identity of commenters and comments may contain links which, if followed, could seriously <strong>damage your computer</strong>. If you are in any doubt, please reject comments <strong>without following any links</strong>.';
$string['moderated_postername'] = 'using the name <strong>{$a}</strong>';
$string['error_alreadyapproved'] = 'Comment already approved or rejected';
$string['error_wrongkey'] = 'Comment key incorrect';
$string['error_unspecified'] = 'The system cannot complete this request because an error occurred ({$a})';
$string['error_moderatednotallowed'] = 'Moderated comments are no longer allowed on this blog or blog post';
$string['moderated_approve'] = 'Approve this comment';
$string['moderated_reject'] = 'Reject this comment';
$string['moderated_rejectedon'] = 'Rejected {$a}:';
$string['moderated_restrictpost'] = 'Restrict commenting on this post';
$string['moderated_restrictblog'] = 'Restrict commenting on all your posts on this blog';
$string['moderated_restrictpage'] = 'Restrict commenting';
$string['moderated_restrictpost_info'] = 'Would you like to restrict comments on this post so that only people who are logged into the system can add comments?';
$string['moderated_restrictblog_info'] = 'Would you like to restrict comments on all your posts on this blog so that only people who are logged into the system can add comments?';
$string['moderated_emailsubject'] = 'Comment awaiting approval on: {$a->blog} ({$a->commenter})';
$string['moderated_emailhtml'] =
'<p>(This is an automatically-generated email. Please do not reply.)</p>
<p>Someone has added a comment to your blog post: {$a->postlink}</p>
<p>You need to <strong>approve the comment</strong> before it will appear in public.</p>
<p>The system does not know the identity of the commenter and comments may
contain links which, if followed, could seriously <strong>damage your
computer</strong>. If you are in any doubt, please reject the comment
<strong>without following any links</strong>.</p>
<p>If you approve the comment, you take responsibility for posting it. Make sure
it doesn\'t contain anything that\'s against the rules.</p>
<hr/>
<p>Name given: {$a->commenter}</p>
<hr/>
<h3>{$a->commenttitle}</h3>
{$a->comment}
<hr/>
<ul class=\'oublog-approvereject\'>
<li><a href=\'{$a->approvelink}\'>{$a->approvetext}</a></li>
<li><a href=\'{$a->rejectlink}\'>{$a->rejecttext}</a></li>
</ul>
<p>
You can also ignore this email. The comment will be rejected automatically
after 30 days.
</p>
<p>
If you receive too many of these emails, you may wish to restrict commenting
to logged-in users only.
</p>
<ul class=\'oublog-restrict\'>
<li><a href=\'{$a->restrictpostlink}\'>{$a->restrictposttext}</a></li>
<li><a href=\'{$a->restrictbloglink}\'>{$a->restrictblogtext}</a></li>
</ul>';
$string['moderated_emailtext'] =
'This is an automatically-generated email. Please do not reply.

Someone has added a comment to your blog post:
{$a->postlink}

You need to approve the comment before it will appear in public.

The system does not know the identity of the commenter and comments may
contain links which, if followed, could seriously damage your computer.
If you are in any doubt, please reject the comment without following any
links.

If you approve the comment, you take responsibility for posting it. Make
sure it doesn\'t contain anything that\'s against the rules.

-----------------------------------------------------------------------
Name given: {$a->commenter}
-----------------------------------------------------------------------
{$a->commenttitle}
{$a->comment}
-----------------------------------------------------------------------

* {$a->approvetext}:
  {$a->approvelink}

* {$a->rejecttext}:
  {$a->rejectlink}

You can also ignore this email. The comment will be rejected automatically
after 30 days.

If you receive too many of these emails, you may wish to restrict
commenting to logged-in users only.

* {$a->restrictposttext}:
  {$a->restrictpostlink}

* {$a->restrictblogtext}:
  {$a->restrictbloglink}
';

$string['displayversion'] = 'OU blog version: <strong>{$a}</strong>';

$string['pluginadministration'] = 'OU Blog administration';
$string['pluginname'] = 'OU Blog';
//help strings
$string['allowcomments_help'] = '&lsquo;Yes, from signed-on users&rsquo; allows comments from users who have access to the blog.

&lsquo;Yes, from everybody&rsquo; allows comments from users and from the general public. You will receive emails to approve or reject comments from users who are not signed in.

&lsquo;No&rsquo; prevents anyone from making a comment on this post.';
$string['individualblogs_help'] = '
<p><strong>No (blog together or in group)</strong>: <em>Individual blogs are not used</em> &ndash;
There are no individual blogs set, everyone is part of a bigger community
(depending on \'Group mode\' setting).</p>
<p><strong>Separate individual blogs</strong>: <em>Individual blogs are used privately</em> &ndash;
Individual users can only post to and see their own blogs,  unless they have permission("viewindividual") to view
other individual blogs.</p>
<p><strong>Visible individual blogs</strong>: <em>Individual blogs are used publically</em> &ndash;
individual users can only post to their own blogs, but they can view other individual blog posts.</p>';

$string['maxvisibility_help'] = '
<p><em>On a personal blog:</em> <strong>Visible only to the blog owner (private)</strong> &ndash;
nobody* else can see this post.</p>
<p><em>On a course blog:</em> <strong>Visible to participants on this course</strong> &ndash; to view the post you must
have been granted access to the blog, usually by being enrolled on the course that contains it.</p>

<p><strong>Visible to everyone who is logged in to the system</strong> &ndash; everyone who is
logged in can view the post, even if they\'re not enrolled on a specific course.</p>
<p><strong>Visible to anyone in the world</strong> &ndash; any Internet user can see this post
if you give them the blog\'s address.</p>

<p>This option exists on the whole blog as well as on individual posts. If the
option is set on the whole blog, that becomes a maximum. For example, if
the whole blog is set to the first level, you cannot change the
level of an individual post at all.</p>';
$string['tags_help'] = 'Tags are labels that help you find and categorise blog posts.';
// Used at OU only
$string['externaldashboardadd'] = 'Add blog to dashboard';
$string['externaldashboardremove'] = 'Remove blog from dashboard';
$string['viewblogdetails'] = 'View blog details';
$string['viewblogposts'] = 'Return to blog';

// User participation
$string['oublog:grade'] = 'Grade OU Blog user participation';
$string['oublog:viewparticipation'] = 'View OU Blog user participation';
$string['userparticipation'] = 'User participation';
$string['myparticipation'] = 'My participation';
$string['savegrades'] = 'Save grades';
$string['participation'] = 'Participation';
$string['participationbyuser'] = 'Participation by user';
$string['details'] = 'Details';
$string['foruser'] = ' for {$a}';
$string['postsby'] = 'Posts by {$a}';
$string['commentsby'] = 'Comments by {$a}';
$string['commentonby'] = 'Comment on <u>{$a->title}</u> by <u>{$a->author}</u>';
$string['nouserposts'] = 'This user made no posts in this blog.';
$string['nousercomments'] = 'This user added no comments in this blog.';
$string['savegrades'] = 'Save grades';
$string['gradesupdated'] = 'Grades updated';
$string['usergrade'] = 'User grade';

// Participation download strings
$string['downloadas'] = 'Download data as';
$string['postauthor'] = 'Post author';
$string['postdate'] = 'Post date';
$string['posttime'] = 'Post time';
$string['posttitle'] = 'Post title';

// Export
$string['exportedpost'] = 'Exported blog post';

$string['configmaxattachments'] = 'Default maximum number of attachments allowed per blog post.';
$string['configmaxbytes'] = 'Default maximum size for all blog attachments on the site.
(subject to course limits and other local settings)';
$string['maxattachmentsize'] = 'Maximum attachment size';
$string['maxattachments'] = 'Maximum number of attachments';
$string['maxattachments_help'] = 'This setting specifies the maximum number of files that can be attached to a blog post.';
$string['maxattachmentsize_help'] = 'This setting specifies the largest size of file that can be attached to a blog post.';
$string['attachments_help'] = 'You can optionally attach one or more files to a blog post. If you attach an image, it will be displayed after the message.';
