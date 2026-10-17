<?php

declare(strict_types=1);

namespace TechNest\Controller\Admin;

use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Support\Flash;
use TechNest\Theme\ThemeRepository;
use TechNest\View\View;

final class ThemeController
{
    public function __construct(
        private readonly View $view,
        private readonly ThemeRepository $themes,
        private readonly Flash $flash,
    ) {}

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('admin/themes', ['themes' => $this->themes->all()]));
    }

    public function activate(Request $request): Response
    {
        if ($this->themes->activate($request->input('theme'))) {
            $this->flash->success('Theme updated for every visitor.');
        } else {
            $this->flash->error('That theme does not exist.');
        }

        return Response::redirect('/admin/themes');
    }
}
