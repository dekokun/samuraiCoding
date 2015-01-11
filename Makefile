.PHONY : clean
default: clean dekokunPhp.zip


dekokunPhp.zip:
	zip -r dekokunPhp.zip . -x 'vendor/symfony/*' 'vendor/facebook/*' 'vendor/phpunit/*' '.git/*' '.idea/*'

clean:
	rm -f dekokunPhp.zip
