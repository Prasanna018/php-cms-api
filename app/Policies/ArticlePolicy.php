<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticlePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Article $article)
    {
        return $user->hasRole('admin') || $article->user_id === $user->id;
    }

    public function create(User $user)
    {
        return $user->hasRole('admin') || $user->hasRole('author');
    }

    public function update(User $user, Article $article)
    {
        return $user->hasRole('admin') || $article->user_id === $user->id;
    }

    public function delete(User $user, Article $article)
    {
        return $user->hasRole('admin') || $article->user_id === $user->id;
    }
}
