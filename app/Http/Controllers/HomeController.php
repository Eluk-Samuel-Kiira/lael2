<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Help Center Page
     */
    public function help()
    {
        return view('home.help');
    }

    /**
     * Documentation Page
     */
    public function docs()
    {
        return view('home.docs');
    }

    /**
     * Blog Page
     */
    public function blog()
    {
        return view('home.blog');
    }

    /**
     * Privacy Policy Page
     */
    public function privacyPolicy()
    {
        return view('home.privacy-policy');
    }

    /**
     * Terms of Service Page
     */
    public function termsOfService()
    {
        return view('home.terms-of-service');
    }
}
