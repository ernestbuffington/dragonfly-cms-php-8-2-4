/*
	@import "poodle"
*/

function switch_provider(e)
{
	var i=1, o, s=(e.target?e.target:e).value;
	for (;i<10;++i) {
		o = Poodle.$('auth-provider-'+i);
		if (o) o.style.display = (i==s)?'block':'none';
	}
}

Poodle.onDOMReady(function(){
	var s = Poodle.$('auth-provider');
	if (s) {
		s.on('change', switch_provider);
		switch_provider(s);
	}
});
