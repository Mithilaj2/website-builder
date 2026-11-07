# Local development only: a tiny SMTP server that saves every email it receives as a file.
#   python3 tools/mailsink.py /tmp/mailsink     (listens on 127.0.0.1:2525; needs: pip install aiosmtpd)
import asyncio, time, os, sys
from aiosmtpd.controller import Controller
OUT = sys.argv[1] if len(sys.argv) > 1 else '/tmp/mailsink'
class H:
    async def handle_DATA(self, server, session, envelope):
        name = os.path.join(OUT, '%d-%s.eml' % (time.time_ns(), envelope.rcpt_tos[0].replace('@', '_at_')))
        with open(name, 'wb') as f:
            f.write(('X-Rcpt: ' + ', '.join(envelope.rcpt_tos) + '\r\n').encode())
            f.write(envelope.original_content or envelope.content)
        return '250 OK'
c = Controller(H(), hostname='127.0.0.1', port=2525)
c.start()
print('listening', flush=True)
while True:
    time.sleep(3600)
