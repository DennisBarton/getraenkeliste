import smtplib, ssl

port = 465  # For SSL
smtp_server = "smtp.strato.de"
sender_email = "dummy@sv-warberg.de"  # Enter your address
receiver_email = "dennis.barton@sv-warberg.de"  # Enter receiver address
password = "ThisIsADummyAccount"
message = """\
Subject: Hi there

This message is sent from Python."""

context = ssl.create_default_context()
with smtplib.SMTP_SSL(smtp_server, port, context=context) as server:
    server.login(sender_email, password)
    server.sendmail(sender_email, receiver_email, message)

