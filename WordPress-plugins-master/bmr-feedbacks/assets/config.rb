# Require any additional compass plugins here.
#require 'breakpoint'
require 'autoprefixer-rails'

# Set this to the root of your project when deployed:
http_path = ".."
css_dir = "css"
sass_dir = "sass"
images_dir = "img"
javascripts_dir = "js"
fonts_dir = "fonts"

encoding = "utf-8"
#environment = :production
environment = :development
cache = true
cache_path = ".sass-cache"
sourcemap = false

# You can select your preferred output style here (can be overridden via the command line):
# output_style = :expanded or :nested or :compact or :compressed


if environment != :production
    output_style = :expanded
    line_comments = true
    disable_warnings = false
    # give us all the info
    disable_warnings = true
    sass_options = {:quiet => true}
  end


  if environment == :production
    output_style = :compressed
    line_comments = false
    # keep the build output nice and clean
    disable_warnings = true
    sass_options = {:quiet => true}
  end


# To enable relative paths to assets via compass helper functions. Uncomment:
# relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
# line_comments = false


# If you prefer the indented syntax, you might want to regenerate this
# project again passing --syntax sass, or you can uncomment this:
# preferred_syntax = :sass
# and then run:
# sass-convert -R --from scss --to sass sass scss && rm -rf sass && mv scss sass

on_stylesheet_saved do |file|
  css = File.read(file)
  File.open(file, 'w') do |io|
    io << AutoprefixerRails.process(css, { browsers: ['last 2 versions'] })
  end
end