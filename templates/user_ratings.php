<?php
$s = new LibertyStars();
$user_stars = $s->getOverallUserRating($gQueryUserId);
$gBitSmarty->assign('loadStars',true);
$gBitSmarty->assign_by_ref('user_stars',$user_stars);

?>