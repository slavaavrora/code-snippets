@inject('cityOptions', 'App\Services\CityOptionsService')

<div class="container">
    <div class="row">
        <div class="col-xs-6 col-sm-6 col-md-3 col-lg-3">
            <h3 class="text-uppercase">{{ config('app.name') }}</h3>
            <ul class="list-unstyled">
                <li>
                    <a href="/events">{{ trans('app.events') }}</a>
                </li>
                <li>
                    <a href="/showplaces">{{ trans('app.showplaces') }}</a>
                </li>
                <li>
                    <a href="/places">{{ trans('app.places') }}</a>
                </li>
                <li>
                    <a href="/recommendations">{{ trans('app.recommendations') }}</a>
                </li>
                <li>
                    <a href="/and-lets">{{ trans('app.and-lets') }}</a>
                </li>
                <li>
                    <a href="/tests">{{  trans('app.tests') }}</a>
                </li>
            </ul>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 text-center">
            <h3 class="text-uppercase">{{ trans('app.subscribe-us') }}</h3>
            <form class="row" name="sidebarSubscribeForm" ng-controller="SubscriptionController as $ctrl">
                <div class="col-xs-12 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
                    <div class="form-group">
                        <input type="email" name="email"
                               class="form-control form-control-primary input-sm text-center"
                               ng-model="$ctrl.email"
                               placeholder="@lang('app.subscribe-email-placeholder')"
                               required="required">
                        <span class="help-text"
                              ng-show="sidebarSubscribeForm.email.$error.email">@lang('app.subscribe-validation-email')</span>
                        <span class="help-text text-danger" ng-show="$ctrl.serverError"
                              ng-bind="$ctrl.serverError"></span>
                    </div>
                </div>
            </form>
            <script type="text/ng-template" id="subscribe-done.html">
                <div class="modal-body">
                    @lang('app.subscribe-done-modal-text')
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="button" ng-click="$ctrl.ok()">{{ trans('app.string.ok') }}</button>
                </div>
            </script>

            <div class="social-buttons">
                <?php
                $nets = $cityOptions->getNetworksByCurrentCity();

                $links = [
                    'net-facebook.com' => '<i class="fa fa-facebook"></i>',
                    'net-vk.com' => '<i class="fa fa-vk"></i>',
                    'net-instagram.com' => '<i class="fa fa-instagram"></i>',
                    'net-twitter.com' => '<i class="fa fa-twitter"></i>',
                    'net-plus.google.com' => '<i class="fa fa-google-plus"></i>',
                ];

                ?>
                @foreach($links as $net => $icon)
                    @if(isset($nets[$net]))
                        <a href="{{ $nets[$net]->value }}" class="btn btn-primary btn-circle">{!! $icon !!}</a>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-3 col-lg-3">
            <h3 class="text-uppercase">{{ trans('app.our-contacts') }}</h3>
            <ul class="list-unstyled">
                <li class="row">
                    <div class="col-lg-4 col-md-5">
                        <i class="fa fa-map-marker fa-fw"></i>
                        <strong>{{ trans('app.address') }}:</strong>
                    </div>
                    <div class="col-lg-8 col-md-7">{{ trans('app.address.street') }}</div>
                </li>
                <li class="row">
                    <div class="col-lg-4 col-md-5">
                        <i class="fa fa-mobile fa-fw"></i>
                        <strong>{{ trans('app.phone') }}:</strong>
                    </div>
                    <div class="col-lg-8 col-md-7">{{ trans('app.address.phone') }}</div>
                </li>
                <li class="row">
                    <div class="col-lg-4 col-md-5">
                        <i class="fa fa-envelope-o fa-fw"></i>
                        <strong>{{ trans('app.email') }}:</strong>
                    </div>
                    <div class="col-lg-8 col-md-7" style="word-wrap: break-word"><a href="/feedback">{{ trans('app.address.email') }}</a></div>
                </li>
            </ul>
        </div>
    </div>
    <p class="text-center"><strong>{{ trans('app.footer-copyright', ['date' => date('Y')]) }}</strong></p>
</div>