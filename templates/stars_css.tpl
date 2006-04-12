{assign var=stars value=$gBitSystem->getConfig('stars_used_in_display',5)}
.stars-rating					{ldelim}list-style:none; margin:0px; padding:0px; width:{$stars*22}px; height:22px; position:relative; background:url( {biticon iname=stars ipackage=stars url=true} ) top left repeat-x;{rdelim}
.stars-rating li				{ldelim}list-style:none; padding:0px; margin:0px; /*\*/ float:left; /* */}
.stars-rating li a				{ldelim}display:block; width:22px; height:22px; text-decoration:none; text-indent:-9000px; z-index:20; position:absolute; padding:0px;{rdelim}
.stars-rating li a:hover		{ldelim}background:url( {biticon iname=stars ipackage=stars url=true} ) left center; z-index:2; left:0px;{rdelim}
{foreach from=$starsLinks item=k key=rate}
	.stars-rating a.stars-{$rate}		{ldelim}left:{$rate*22-22}px;{rdelim}
	.stars-rating a.stars-{$rate}:hover	{ldelim}width:{$rate*22}px;{rdelim}
{/foreach}
.stars-rating .stars-current	{ldelim}background:url( {biticon iname=stars ipackage=stars url=true} ) left bottom; position:absolute; height:22px; display:block; text-indent:-9000px; z-index:1;{rdelim}
