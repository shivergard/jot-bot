#/bin/sh
if [ -f storage/database.sqlite ];
then
	read -r -p " ¯\(°_o)/¯ Are you sure? (rm rm storage/database.sqlite ) [y/N] " response
	case $response in
	    [yY][eE][sS]|[yY]) 
	        rm storage/database.sqlite
			echo "DB DROPED ¯\(°_o)/¯"
	        ;;
	    *)
	        echo "¯\(°_o)/¯"
	        exit
	        ;;
	esac
fi

touch storage/database.sqlite
chmod -fR 777 storage/database.sqlite
