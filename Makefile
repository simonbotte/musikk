.PHONY: start stop

start:
	cd docker && docker-compose up --build -d

stop:
	cd docker && docker-compose down
