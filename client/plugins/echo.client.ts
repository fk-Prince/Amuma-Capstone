
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
        enabledTransports: isSecure ? ['wss'] : ['ws'],
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
                            return JSON.parse(text)
                        })
                        .then((data) => callback(null, data))
                        .catch((err) => {
                            callback(err, null)
                        })
                },
            }
        },
    })

    return {
        provide: {
            echo,
        },
    }
})
