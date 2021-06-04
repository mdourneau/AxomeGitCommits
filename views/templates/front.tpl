<!-- Block axome_git_commits -->
<div id="axome_git_commits" class="block">
    <h2>{$username} / {$repository}</h2>
    <ul class="list-group">
        {foreach $commits as $commit}
            <li class="list-group-item">
                <div>
                    <p class="message"><a href="{$commit.html_url}" target="_blank">{$commit.commit.message}</a></p>
                    <p class="author"><span class="name">{$commit.commit.author.name}</span>{l s=" committed on " d="Module.axome_git_commits"}{$commit.commit.author.date|truncate:10:""}</p>
                </div>
                <span class="badge">{$commit.sha|truncate:5:""}</span>
            </li>
        {/foreach}
    </ul>
</div>
<!-- /Block axome_git_commits -->