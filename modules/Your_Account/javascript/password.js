
Poodle.onDOMReady(function()
{
	var ps = Poodle.$("password_strength"),
		pi = Poodle.$Q("input[name='new_password']",1);
	if (!pi) {
		pi = Poodle.$("register-password");
	}
	if (pi) {
		if (!ps) {
			ps = pi.$C('meter',{min:0, low:35, optimum:85, high:70, max:100, value:0, title:"Password strength", style:{marginLeft:'0.5em'}}).placeAfter(pi);
		}
		pi.on('change keyup',function(){
			var v = this.value, m, re = [/[^0-9A-Za-z]+/g, /[0-9]+/g, /[A-Z]+/g, /[a-z]+/g],
				i = 1,
				l = v.length,
				s = l?1:0,
				c = 0,
				ii = 0;
			for (; i < l; ++i) {
				if (v[i] != v[i-1]) {
					++s;
				} else {
					s -= 0.5;
				}
			}
			for (i = 0; i < 4; ++i) {
				if (m = v.match(re[i])) {
					++c;
					for (; ii < m.length; ++ii) {
						if (5 > m[ii].length) {
							++s;
						}
					}
				}
			}
			s = (s / 3 * c);
			ps.value = Math.max(1, Math.min(100, s * 5));
		});
	}
});
