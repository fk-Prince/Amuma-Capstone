
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'))
    return match ? decodeURIComponent(match[2]!) : null
}


export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig();

    const backendUrl = new URL(config.public.backendApi as string);
    const isSecure = backendUrl.protocol === 'https:';

    (window as any).Pusher = Pusher;

    const echo = new Echo({
        broadcaster: 'reverb',
        key: 'lbcswwvuj6gh7s5cmwza',
        wsHost: backendUrl.hostname,
        wsPort: 8080,
        wssPort: 9443,
        forceTLS: isSecure,
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel: any) => {
            return {
                authorize: (socketId: string, callback: Function) => {
                    fetch(`${config.public.backendApi}/broadcasting/auth`, {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '',
                        },
                        body: JSON.stringify({
                            socket_id: socketId,
                            channel_name: channel.name,
                        }),
                    })
                        .then(async (res) => {
                            const text = await res.text()

                            if (!res.ok) {
                                console.error('[echo] auth failed', res.status, channel.name, text.slice(0, 300))
                            }

                            return JSON.parse(text)
                        })
                        .then((data) => callback(null, data))
                        .catch((err) => {
                            console.error('[echo] auth error', channel.name, err)
                            callback(err, null)
                        })
                },
            }
        },
    })

    // console.info('[echo] init', {
    //     wsHost: backendUrl.hostname,
    //     wsPort: 8080,
    //     wssPort: 9443,
    //     forceTLS: isSecure,
    // })

    const pusher = (echo as any).connector?.pusher
    const connection = pusher?.connection

    // console.info('[echo] connector exists?', !!(echo as any).connector, 'pusher exists?', !!pusher, 'connection exists?', !!connection)
    // console.info('[echo] connection state right now =', connection?.state)

    connection?.bind('state_change', (states: any) => {
        // console.info('[echo] connection', states.previous, '->', states.current)
    })

    connection?.bind('error', (err: any) => {
        // console.error('[echo] connection error', JSON.stringify(err))
    })

    connection?.bind('connected', () => {
        // console.info('[echo] connected, socket_id =', connection.socket_id)
    })

    // setTimeout(() => {
    //     // console.info('[echo] state after 5s =', connection?.state)
    // }, 5000)

    return {
        provide: {
            echo,
        },
    }
})
