<?php


namespace Bmr\Assistant;


interface QuizInterface
{
    public function getPartials();
    public function getPartialAction();
    public function createPageIfNotExists();
    public function loadStyles();
    public function loadScripts();
    public function isCurrent();
}