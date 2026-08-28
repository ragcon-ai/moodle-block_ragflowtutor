// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * RAGflow Tutor knowledge-base panel: shows KB/assistant status lights and the document list, and (for a
 * Moodle-managed KB) lets a manager add/remove documents. Polls while anything is still parsing.
 *
 * @module     block_ragflowtutor/manage
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {getStrings} from 'core/str';
import Notification from 'core/notification';

/**
 * Build a traffic-light dot element.
 *
 * @param {String} status red|yellow|green
 * @param {String} title Optional hover text.
 * @return {HTMLElement}
 */
const dot = (status, title) => {
    const span = document.createElement('span');
    span.className = 'rfdtutor-dot rfdtutor-dot--' + (['red', 'yellow', 'green'].includes(status) ? status : 'red');
    if (title) {
        span.title = title;
    }
    return span;
};

/**
 * Init the panel.
 *
 * @param {Number} blockinstanceid The block instance id.
 * @param {Number} uploadlimit Max upload size in bytes (0 = unlimited).
 * @param {String} wwwroot Moodle root URL.
 * @param {String} sesskey Session key.
 */
export const init = (blockinstanceid, uploadlimit, wwwroot, sesskey) => {
    const panel = document.querySelector(
        '[data-region="rfdtutor-kbpanel"][data-blockinstanceid="' + blockinstanceid + '"]'
    );
    if (!panel) {
        return;
    }
    const titleEl = panel.querySelector('[data-region="rfdtutor-title"]');
    const statusDotEl = panel.querySelector('[data-region="rfdtutor-statusdot"]');
    const refreshBtn = panel.querySelector('[data-action="rfdtutor-refresh"]');
    const filesEl = panel.querySelector('[data-region="rfdtutor-files"]');
    const uploadEl = panel.querySelector('[data-region="rfdtutor-upload"]');
    let timer = null;
    let str = {};

    const fileTitle = (f) => {
        let base;
        if (f.status === 'green') {
            base = str.filedone.replace('{$a}', f.chunkcount);
        } else if (f.status === 'red') {
            base = str.fileerror;
        } else {
            base = str.filepending;
        }
        // Append RAGflow's own processing details (progress message) when present.
        return f.message ? base + '\n' + f.message : base;
    };

    const iconButton = (cls, icon, title, onclick) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-icon ' + cls;
        btn.title = title;
        btn.setAttribute('aria-label', title);
        btn.innerHTML = '<i class="icon fa ' + icon + '" aria-hidden="true"></i>';
        btn.addEventListener('click', onclick);
        return btn;
    };

    // Only http(s) or root-relative URLs are safe to navigate to (the server already validates the proxy
    // URL); anything else is dropped.
    const safeUrl = (url) => (/^(https?:\/\/|\/)/i).test(url || '') ? url : '';

    // Download button: the signed URL is minted on click (block_ragflowtutor_download_url) so the token
    // lives only seconds. A popup opened synchronously (before the async call) avoids the browser blocking
    // window.open from within the callback.
    const downloadButton = (docid) => {
        const a = document.createElement('button');
        a.type = 'button';
        a.className = 'btn btn-icon rfdtutor-download';
        a.title = str.download;
        a.setAttribute('aria-label', str.download);
        a.innerHTML = '<i class="icon fa fa-download" aria-hidden="true"></i>';
        a.addEventListener('click', () => {
            const win = window.open('', '_blank');
            Ajax.call([{
                methodname: 'block_ragflowtutor_download_url',
                args: {blockinstanceid, docid},
            }])[0].then((res) => {
                const url = safeUrl(res.url);
                if (url) {
                    if (win) {
                        win.location = url;
                    } else {
                        window.location = url;
                    }
                } else if (win) {
                    win.close();
                }
                return url;
            }).catch(() => {
                if (win) {
                    win.close();
                }
            });
        });
        return a;
    };

    const renderFiles = (files) => {
        filesEl.innerHTML = '';
        if (!files.length) {
            const empty = document.createElement('div');
            empty.className = 'text-muted small';
            empty.textContent = str.nofiles;
            filesEl.appendChild(empty);
            return;
        }
        const list = document.createElement('ul');
        list.className = 'rfdtutor-filelist';
        files.forEach((f) => {
            const li = document.createElement('li');
            li.className = 'rfdtutor-fileitem';
            li.appendChild(dot(f.status, fileTitle(f)));
            const nm = document.createElement('span');
            nm.className = 'rfdtutor-filename';
            nm.textContent = f.name;
            li.appendChild(nm);
            li.appendChild(downloadButton(f.id));
            // Action icon (aligned with RAGflow): a play triangle to start processing a never-processed
            // document, spinning arrows while a parse runs, circular arrows to re-process otherwise.
            const neverprocessed = f.status === 'yellow' && !f.processing;
            const icon = neverprocessed ? 'fa-play' : 'fa-sync';
            const title = neverprocessed ? str.process : str.reparse;
            const actionBtn = iconButton('rfdtutor-reparse', icon, title, () => reparseFile(f.id));
            if (f.processing) {
                const i = actionBtn.querySelector('i');
                if (i) {
                    i.classList.add('fa-spin');
                }
            }
            li.appendChild(actionBtn);
            li.appendChild(iconButton('rfdtutor-del', 'fa-trash', str.delete, () => deleteFile(f.id, f.name)));
            list.appendChild(li);
        });
        filesEl.appendChild(list);
    };

    // Overall traffic light for the assistant / KB: red if the assistant is unreachable or a Moodle-managed
    // KB is missing, yellow only while the KB itself is still linking, otherwise green. Individual document
    // processing does NOT turn this yellow (it is shown per file + on the spinning refresh icon) so the KB
    // status stays green and is not misread as "the knowledge base is broken".
    const overall = (data) => {
        if (data.chatstatus === 'red' || (data.ismoodlekb && data.kbstatus === 'red')) {
            return 'red';
        }
        if (data.ismoodlekb && data.kbstatus === 'yellow') {
            return 'yellow';
        }
        return 'green';
    };

    const setSpin = (on) => {
        if (!refreshBtn) {
            return;
        }
        const icon = refreshBtn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-spin', on);
        }
    };

    const scheduleIfBusy = (data) => {
        const kbbusy = data.ismoodlekb && data.kbstatus === 'yellow';
        const filesbusy = (data.files || []).some((f) => f.status === 'yellow');
        // The header "refresh statuses" icon reflects only KB/assistant status changes (linking) – not
        // document processing (that is shown by the per-file dot + its spinning re-process icon).
        setSpin(kbbusy);
        const busy = kbbusy || filesbusy;
        if (timer) {
            window.clearTimeout(timer);
            timer = null;
        }
        if (busy) {
            timer = window.setTimeout(refresh, 4000);
        }
    };

    const render = (data) => {
        // Title = assistant name; below it the overall status dot (the config only changes the assistant,
        // and the KB is bound to it, so a separate KB dot would only confuse).
        titleEl.textContent = data.chatname || '—';
        statusDotEl.innerHTML = '';
        statusDotEl.appendChild(dot(overall(data), data.statusmessage));
        if (data.ismoodlekb) {
            renderFiles(data.files || []);
            renderUpload();
            uploadEl.hidden = false;
        } else {
            filesEl.innerHTML = '';
            uploadEl.hidden = true;
        }
        scheduleIfBusy(data);
    };

    const refresh = () => Ajax.call([{
        methodname: 'block_ragflowtutor_get_status',
        args: {blockinstanceid},
    }])[0].then(render).catch(Notification.exception);

    const deleteFile = (docid, name) => {
        if (!window.confirm(str.confirmdelete.replace('{$a}', name))) { // eslint-disable-line no-alert
            return;
        }
        Ajax.call([{
            methodname: 'block_ragflowtutor_delete_file',
            args: {blockinstanceid, docid},
        }])[0].then((res) => {
            if (!res.ok && res.error) {
                Notification.addNotification({message: res.error, type: 'error'});
            }
            return refresh();
        }).catch(Notification.exception);
    };

    const reparseFile = (docid) => {
        Ajax.call([{
            methodname: 'block_ragflowtutor_reparse_file',
            args: {blockinstanceid, docid},
        }])[0].then((res) => {
            if (!res.ok && res.error) {
                Notification.addNotification({message: res.error, type: 'error'});
            }
            return refresh();
        }).catch(Notification.exception);
    };

    const uploadOne = (file) => {
        if (uploadlimit > 0 && file.size > uploadlimit) {
            Notification.addNotification({message: str.toolarge, type: 'error'});
            return Promise.resolve();
        }
        const fd = new FormData();
        fd.append('sesskey', sesskey);
        fd.append('blockinstanceid', blockinstanceid);
        fd.append('file', file);
        return fetch(wwwroot + '/blocks/ragflowtutor/upload.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
        }).then((r) => r.json()).then((res) => {
            if (!res.ok && res.error) {
                Notification.addNotification({message: res.error, type: 'error'});
            }
            return null;
        });
    };

    // A fresh "Add file" control (labelled hidden file input) that uploads sequentially, then refreshes.
    const buildAddButton = () => {
        const label = document.createElement('label');
        label.className = 'btn btn-secondary btn-sm mb-0';
        label.textContent = str.addfile;
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.multiple = true;
        fileInput.className = 'sr-only';
        fileInput.addEventListener('change', () => {
            const files = Array.from(fileInput.files || []);
            fileInput.value = '';
            if (!files.length) {
                return;
            }
            label.classList.add('disabled');
            files.reduce((p, f) => p.then(() => uploadOne(f)), Promise.resolve())
                .then(() => {
                    label.classList.remove('disabled');
                    return refresh();
                }).catch(() => {
                    label.classList.remove('disabled');
                });
        });
        label.appendChild(fileInput);
        return label;
    };

    // The upload area: the data notice, then the "Add file" button.
    const renderUpload = () => {
        uploadEl.innerHTML = '';
        const notice = document.createElement('div');
        notice.className = 'alert alert-info small rfdtutor-uploadnotice';
        notice.textContent = str.uploadnotice;
        uploadEl.appendChild(notice);
        uploadEl.appendChild(buildAddButton());
    };

    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => refresh());
    }

    getStrings([
        {key: 'kbpanel:nofiles', component: 'block_ragflowtutor'},
        {key: 'kbpanel:delete', component: 'block_ragflowtutor'},
        {key: 'kbpanel:reparse', component: 'block_ragflowtutor'},
        {key: 'kbpanel:process', component: 'block_ragflowtutor'},
        {key: 'kbpanel:download', component: 'block_ragflowtutor'},
        {key: 'kbpanel:confirmdelete', component: 'block_ragflowtutor'},
        {key: 'kbpanel:addfile', component: 'block_ragflowtutor'},
        {key: 'kbpanel:filetoolarge', component: 'block_ragflowtutor'},
        {key: 'kbpanel:filedone', component: 'block_ragflowtutor'},
        {key: 'kbpanel:filepending', component: 'block_ragflowtutor'},
        {key: 'kbpanel:fileerror', component: 'block_ragflowtutor'},
        {key: 'kbpanel:uploadnotice', component: 'block_ragflowtutor'},
    ]).then((s) => {
        str = {
            nofiles: s[0], 'delete': s[1], reparse: s[2], process: s[3], download: s[4], confirmdelete: s[5],
            addfile: s[6], toolarge: s[7], filedone: s[8], filepending: s[9], fileerror: s[10],
            uploadnotice: s[11],
        };
        return refresh();
    }).catch(Notification.exception);
};
